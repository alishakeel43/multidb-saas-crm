<?php

namespace Webkul\Assignment\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Assignment\Repositories\AssignmentRuleRepository;
use Webkul\User\Repositories\UserRepository;

class AssignmentRuleController extends Controller
{
    public function __construct(
        protected AssignmentRuleRepository $assignmentRuleRepository,
        protected UserRepository $userRepository,
    ) {}

    public function index(): View
    {
        $rules = $this->assignmentRuleRepository
            ->getModel()
            ->newQuery()
            ->with('users')
            ->orderByDesc('priority')
            ->orderBy('id')
            ->get();

        $users = $this->assignableUsers();

        return view('assignment::settings.index', compact('rules', 'users'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'strategy' => 'required|in:round_robin,least_workload,first_available',
            'only_when_empty' => 'sometimes|boolean',
            'priority' => 'nullable|integer|min:0',
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'integer',
        ]);

        $userIds = $this->filterAssignableUserIds($data['user_ids']);

        if ($userIds === []) {
            session()->flash('error', trans('assignment::app.messages.no-assignable-users'));

            return redirect()->route('admin.settings.assignment.index');
        }

        $rule = $this->assignmentRuleRepository->create([
            'name' => $data['name'],
            'entity_type' => 'leads',
            'strategy' => $data['strategy'],
            'is_active' => true,
            'only_when_empty' => (bool) ($data['only_when_empty'] ?? true),
            'priority' => (int) ($data['priority'] ?? 0),
        ]);

        $sync = [];
        foreach (array_values($userIds) as $index => $userId) {
            $sync[$userId] = ['sort_order' => $index, 'is_active' => true];
        }
        $rule->users()->sync($sync);

        session()->flash('success', trans('assignment::app.messages.created'));

        return redirect()->route('admin.settings.assignment.index');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $rule = $this->assignmentRuleRepository->findOrFail($id);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'strategy' => 'required|in:round_robin,least_workload,first_available',
            'is_active' => 'sometimes|boolean',
            'only_when_empty' => 'sometimes|boolean',
            'priority' => 'nullable|integer|min:0',
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'integer',
        ]);

        $userIds = $this->filterAssignableUserIds($data['user_ids']);

        if ($userIds === []) {
            session()->flash('error', trans('assignment::app.messages.no-assignable-users'));

            return redirect()->route('admin.settings.assignment.index');
        }

        $this->assignmentRuleRepository->update([
            'name' => $data['name'],
            'strategy' => $data['strategy'],
            'is_active' => (bool) ($data['is_active'] ?? $rule->is_active),
            'only_when_empty' => (bool) ($data['only_when_empty'] ?? $rule->only_when_empty),
            'priority' => (int) ($data['priority'] ?? 0),
        ], $id);

        $rule = $this->assignmentRuleRepository->findOrFail($id);

        $sync = [];
        foreach (array_values($userIds) as $index => $userId) {
            $sync[$userId] = ['sort_order' => $index, 'is_active' => true];
        }
        $rule->users()->sync($sync);

        session()->flash('success', trans('assignment::app.messages.updated'));

        return redirect()->route('admin.settings.assignment.index');
    }

    public function destroy(int $id): RedirectResponse
    {
        $this->assignmentRuleRepository->delete($id);

        session()->flash('success', trans('assignment::app.messages.deleted'));

        return redirect()->route('admin.settings.assignment.index');
    }

    /**
     * Active tenant users excluding platform support/debug accounts.
     */
    private function assignableUsers(): Collection
    {
        $repository = $this->userRepository;

        if (Schema::connection('tenant')->hasColumn('users', 'is_support_account')) {
            $repository = $repository->scopeQuery(
                fn ($query) => $query->where('is_support_account', false)
            );
        }

        return $repository->findWhere(['status' => 1]);
    }

    /**
     * @param  list<int|string>  $userIds
     * @return list<int>
     */
    private function filterAssignableUserIds(array $userIds): array
    {
        $allowed = $this->assignableUsers()->pluck('id')->map(fn ($id) => (int) $id)->all();

        return array_values(array_intersect(array_map('intval', $userIds), $allowed));
    }
}
