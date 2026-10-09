<?php

namespace Webkul\User\Repositories;

use App\Models\Tenant;
use Webkul\Core\Eloquent\Repository;

class UserRepository extends Repository
{
    /**
     * Searchable fields
     */
    protected $fieldSearchable = [
        'name',
        'email',
        'status',
        'view_permission',
        'role_id',
    ];

    /**
     * Specify Model class name
     *
     * @return mixed
     */
    public function model()
    {
        return 'Webkul\User\Contracts\User';
    }

    /**
     * Scope user queries to the active tenant workspace.
     *
     * CRM users live in each tenant's isolated database, so an active tenant
     * needs no additional membership join. Outside a tenant context, deny the
     * query rather than exposing users from whichever database is configured.
     */
    public function scopeToWorkspace(): static
    {
        return $this->scopeQuery(function ($query) {
            return Tenant::current()
                ? $query
                : $query->whereRaw('0 = 1');
        });
    }

    /**
     * This function will return user ids of current user's groups
     *
     * @return array
     */
    public function getCurrentUserGroupsUserIds()
    {
        $userIds = $this->scopeQuery(function ($query) {
            return $query->select('users.*')
                ->leftJoin('user_groups', 'users.id', '=', 'user_groups.user_id')
                ->leftJoin('groups', 'user_groups.group_id', 'groups.id')
                ->whereIn('groups.id', auth()->guard('user')->user()->groups()->pluck('id'));
        })->get()->pluck('id')->toArray();

        return $userIds;
    }
}
