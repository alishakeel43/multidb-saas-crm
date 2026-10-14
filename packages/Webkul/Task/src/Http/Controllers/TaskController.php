<?php

namespace Webkul\Task\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Requests\MassDestroyRequest;
use Webkul\Admin\Http\Requests\MassUpdateRequest;
use Webkul\Task\DataGrids\TaskDataGrid;
use Webkul\Task\Http\Requests\TaskRequest;
use Webkul\Task\Models\Task;
use Webkul\Task\Repositories\TaskRepository;
use Webkul\Task\Repositories\TaskStageRepository;
use Webkul\Task\Services\TaskEntityLookup;
use Webkul\Task\Services\TaskWorkflow;
use Webkul\User\Models\User;
use Webkul\User\Repositories\UserRepository;

class TaskController extends Controller
{
    public function __construct(
        protected TaskWorkflow $workflow,
        protected TaskRepository $taskRepository,
        protected TaskStageRepository $stageRepository,
        protected TaskEntityLookup $entities,
        protected UserRepository $userRepository,
    ) {}

    public function index()
    {
        if (request()->ajax() && request('layout') !== 'kanban') {
            return datagrid(TaskDataGrid::class)->process();
        }

        $userId = $this->statsUserId();

        return view('task::index', [
            'stats' => $this->workflow->stats($userId),
            'stages' => $this->stageRepository->ensureDefaults(),
            'columns' => request('layout') === 'kanban' ? $this->workflow->kanban(request()->only(['assigned_to', 'priority', 'search', 'bucket'])) : [],
            'users' => $this->assignableUsers(),
            'layout' => request('layout') === 'kanban' ? 'kanban' : 'table',
        ]);
    }

    public function create(): View
    {
        return view('task::create', [
            'stages' => $this->stageRepository->ensureDefaults(),
            'users' => $this->assignableUsers(),
            'defaults' => [
                'entity_type' => request('entity_type'),
                'entity_id' => request('entity_id'),
                'status' => $this->workflow->defaultStatus(),
            ],
        ]);
    }

    public function store(TaskRequest $request): RedirectResponse
    {
        $task = $this->workflow->create($request->validated());

        session()->flash('success', trans('task::app.messages.created'));

        return redirect()->route('admin.tasks.index');
    }

    public function view(int $id): View
    {
        $task = $this->findAuthorized($id);

        return view('task::view', [
            'task' => $task,
            'stages' => $this->stageRepository->ensureDefaults(),
            'linked' => $task->linkedEntity(),
            'related' => $task->entity_type && $task->entity_id
                ? $this->workflow->forEntity($task->entity_type, (int) $task->entity_id)->where('id', '!=', $task->id)->values()
                : collect(),
        ]);
    }

    public function edit(int $id): View
    {
        $task = $this->findAuthorized($id);

        return view('task::edit', [
            'task' => $task,
            'stages' => $this->stageRepository->ensureDefaults(),
            'users' => $this->assignableUsers(),
            'linked' => $task->linkedEntity(),
        ]);
    }

    public function update(TaskRequest $request, int $id): RedirectResponse
    {
        $task = $this->findAuthorized($id);
        $this->workflow->update($task, $request->validated());

        session()->flash('success', trans('task::app.messages.updated'));

        return redirect()->route('admin.tasks.view', $id);
    }

    public function updateStatus(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'string', 'max:50'],
        ]);

        $task = $this->findAuthorized($id);
        $this->workflow->changeStatus($task, $data['status']);

        if ($request->expectsJson()) {
            return response()->json(['message' => trans('task::app.messages.status-updated')]);
        }

        session()->flash('success', trans('task::app.messages.status-updated'));

        return back();
    }

    public function destroy(int $id): JsonResponse|RedirectResponse
    {
        $task = $this->findAuthorized($id);
        $this->taskRepository->delete($task->id);

        if (request()->expectsJson()) {
            return response()->json(['message' => trans('task::app.messages.deleted')]);
        }

        session()->flash('success', trans('task::app.messages.deleted'));

        return redirect()->route('admin.tasks.index');
    }

    public function massUpdate(MassUpdateRequest $request): JsonResponse
    {
        $ids = $this->authorizedIds($request->input('indices', []));
        $this->workflow->massStatus($ids, (string) $request->input('value'));

        return response()->json(['message' => trans('task::app.messages.mass-updated')]);
    }

    public function massDestroy(MassDestroyRequest $request): JsonResponse
    {
        $ids = $this->authorizedIds($request->input('indices', []));
        $this->workflow->massDelete($ids);

        return response()->json(['message' => trans('task::app.messages.mass-deleted')]);
    }

    public function searchEntities(Request $request): JsonResponse
    {
        $data = $request->validate([
            'entity_type' => ['required', 'string'],
            'search' => ['nullable', 'string'],
        ]);

        return response()->json([
            'data' => $this->entities->search($data['entity_type'], (string) ($data['search'] ?? '')),
        ]);
    }

    public function entityTasks(string $entityType, int $entityId): JsonResponse
    {
        return response()->json([
            'data' => $this->workflow->forEntity($entityType, $entityId)->map(fn (Task $task) => [
                'id' => $task->id,
                'title' => $task->title,
                'status' => $task->status,
                'priority' => $task->priority,
                'due_date' => optional($task->due_date)->toDateString(),
                'url' => route('admin.tasks.view', $task->id),
            ]),
        ]);
    }

    public function dashboardStats(): JsonResponse
    {
        return response()->json($this->workflow->stats($this->statsUserId()));
    }

    public function kanbanData(Request $request): JsonResponse
    {
        return response()->json([
            'columns' => collect($this->workflow->kanban($request->only(['assigned_to', 'priority', 'search', 'bucket'])))
                ->map(function (array $column) {
                    $column['tasks'] = collect($column['tasks'])->map(fn (Task $task) => [
                        'id' => $task->id,
                        'title' => $task->title,
                        'priority' => $task->priority,
                        'due_date' => optional($task->due_date)->toDateString(),
                        'due_time' => $task->due_time ? substr((string) $task->due_time, 0, 5) : null,
                        'is_overdue' => $task->is_overdue,
                        'assignee' => $task->assignedUser?->name,
                        'url' => route('admin.tasks.view', $task->id),
                    ])->values();

                    return $column;
                })->values(),
        ]);
    }

    public function storeStage(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'color' => ['nullable', 'string', 'max:20'],
        ]);

        $stages = $this->stageRepository->ensureDefaults();

        $this->stageRepository->create([
            'code' => $this->stageRepository->codeFromName($data['name']),
            'name' => $data['name'],
            'color' => $data['color'] ?: '#2563EB',
            'sort_order' => ((int) $stages->max('sort_order')) + 1,
            'is_default' => false,
        ]);

        session()->flash('success', trans('task::app.messages.stage-created'));

        return back();
    }

    public function updateStage(Request $request, string $code): RedirectResponse
    {
        $stage = $this->stageRepository->findOneByField('code', $code);
        abort_if(! $stage, 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'color' => ['nullable', 'string', 'max:20'],
            'sort_order' => ['nullable', 'integer', 'min:1'],
        ]);

        $this->stageRepository->update([
            'name' => $data['name'],
            'color' => $data['color'] ?: $stage->color,
            'sort_order' => $data['sort_order'] ?? $stage->sort_order,
        ], $stage->id);

        session()->flash('success', trans('task::app.messages.stage-updated'));

        return back();
    }

    public function destroyStage(Request $request, string $code): RedirectResponse
    {
        $stage = $this->stageRepository->findOneByField('code', $code);
        abort_if(! $stage, 404);

        if ($this->stageRepository->count() <= 1) {
            session()->flash('error', trans('task::app.messages.stage-required'));

            return back();
        }

        $taskCount = Task::query()->where('status', $code)->count();

        if ($taskCount > 0) {
            $data = $request->validate([
                'reassign_to' => ['required', 'string', 'max:50'],
            ]);
            $this->workflow->moveStage($code, $data['reassign_to']);
        }

        $this->stageRepository->delete($stage->id);

        session()->flash('success', trans('task::app.messages.stage-deleted'));

        return back();
    }

    private function findAuthorized(int $id): Task
    {
        $task = $this->taskRepository->find($id);
        abort_if(! $task, 404);
        $this->preventUnauthorizedAccess($task->ownerIds());

        return $task;
    }

    /**
     * @param  array<int, int|string>  $ids
     * @return array<int, int>
     */
    private function authorizedIds(array $ids): array
    {
        $records = $this->filterAuthorizedRecords(
            $this->taskRepository->findWhereIn('id', $ids),
            fn (Task $task) => $task->ownerIds(),
        );

        return $records->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    private function statsUserId(): ?int
    {
        $user = auth()->guard('user')->user();

        if ($user && bouncer()->hasPermission('tasks') && $user->view_permission !== 'global') {
            return (int) $user->id;
        }

        return null;
    }

    private function assignableUsers(): Collection
    {
        $repository = $this->userRepository->scopeQuery(fn ($query) => User::applySupportExclusion($query));

        return $repository->findWhere(['status' => 1]);
    }
}
