<?php

namespace Webkul\User\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;
use Webkul\User\Contracts\User as UserContract;

class User extends Authenticatable implements UserContract
{
    use HasApiTokens, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'image',
        'password',
        'api_token',
        'role_id',
        'status',
        'view_permission',
        'created_by',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'api_token',
        'remember_token',
    ];

    /**
     * Get image url for the product image.
     */
    public function image_url()
    {
        if (! $this->image) {
            return;
        }

        return Storage::url($this->image);
    }

    /**
     * Get image url for the product image.
     */
    public function getImageUrlAttribute()
    {
        return $this->image_url();
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $array = parent::toArray();

        $array['image_url'] = $this->image_url;

        return $array;
    }

    /**
     * Get the role that owns the user.
     */
    public function role()
    {
        return $this->belongsTo(RoleProxy::modelClass());
    }

    /**
     * The groups that belong to the user.
     */
    public function groups()
    {
        return $this->belongsToMany(GroupProxy::modelClass(), 'user_groups');
    }

    /**
     * Checks if user has permission to perform certain action.
     *
     * @param  string  $permission
     * @return bool
     */
    public function hasPermission($permission)
    {
        if ($this->role->permission_type == 'custom' && ! $this->role->permissions) {
            return false;
        }

        return in_array($permission, $this->role->permissions);
    }

    /**
     * Hide platform support accounts from assign-to / owner / staff pickers.
     * A user is "support" when users.is_support_account = 1 OR their role has
     * roles.is_support_role = 1. Works per tenant (tenant connection schema).
     * Accepts a Model, Builder or Relation; always use the returned value.
     */
    public function scopeExcludeSupport($query)
    {
        return static::applySupportExclusion($query);
    }

    public static function applySupportExclusion($query)
    {
        $schema = \Illuminate\Support\Facades\Schema::connection('tenant');

        if ($schema->hasColumn('users', 'is_support_account')) {
            $query = $query->where(function ($q) {
                $q->where('users.is_support_account', false)->orWhereNull('users.is_support_account');
            });
        }

        if ($schema->hasColumn('roles', 'is_support_role')) {
            $query = $query->where(function ($q) {
                $q->whereNull('users.role_id')->orWhereNotIn('users.role_id', function ($sub) {
                    $sub->select('id')->from('roles')->where('is_support_role', true);
                });
            });
        }

        return $query;
    }
}
