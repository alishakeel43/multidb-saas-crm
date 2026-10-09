<?php

namespace Webkul\Task\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Webkul\Activity\Repositories\ActivityRepository;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Contact\Models\Organization;
use Webkul\Contact\Models\Person;
use Webkul\Contact\Repositories\OrganizationRepository;
use Webkul\Contact\Repositories\PersonRepository;
use Webkul\DataGrid\ColumnTypes\Date as DateColumn;
use Webkul\Lead\Models\Lead;
use Webkul\Lead\Repositories\LeadRepository;
use Webkul\Quote\Models\Quote;
use Webkul\Quote\Repositories\QuoteRepository;
use Webkul\Task\DataGrids\TaskDataGrid;
use Webkul\Task\Http\Requests\TaskRequest;
use Webkul\Task\Http\Requests\UpdateStatusRequest;
use Webkul\Task\Http\Resources\TaskResource;
use Webkul\Task\Models\Task;
use Webkul\Task\Repositories\TaskRepository;
use Webkul\Task\Repositories\TaskStageRepository;
use Webkul\User\Repositories\UserRepository;

class TaskController extends Controller
{
    public function __construct(
        protected TaskRepository $taskRepository,
        protected UserRepository $userRepository,
        protected LeadRepository $leadRepository,
        protected PersonRepository $personRepository,
        protected OrganizationRepository $organizationRepository,
        protected QuoteRepository $quoteRepository,
        protected TaskStageRepository $taskStageRepository
    ) {}

    /**
     * Display a listing of tasks or datagrid json response.
     */
    public function index()
    {
        if (request()->ajax()) {
            if (request('format') === 'kanban') {
                return $this->kanbanData();
            }

            return datagrid(TaskDataGrid::class)->process();
        }

        $userId = bouncer()->hasPermission('tasks') && auth()->guard('user')->user()->view_permission !== 'global'
            ? auth()->id()
            : null;

        $stats = $this->taskRepository->getTaskStats($userId);

        $assignableUsers = $this->getAssignableUsers();

        $pipeline = (object) [
            'id' => 'default',
            'name' => trans('task::app.title', ['default' => 'Tasks Pipeline']),
            'stages' => collect($this->getStagesList()),
        ];

        return view('task::index', [
            'stats' => $stats,
            'users' => $assignableUsers,
            'assignableUsers' => $assignableUsers,
            'pipeline' => $pipeline,
            'columns' => $this->getKanbanColumns(),
        ]);
    }

    /**
     * Get tasks structured by status stages for Kanban pipeline view (matches Lead get).
     */
    public function get(): JsonResponse
    {
        $stages = $this->getStagesList();

        if ($stageId = request()->query('stage_id') ?? request()->query('pipeline_stage_id')) {
            $stages = array_filter($stages, fn ($s) => (string) $s['id'] === (string) $stageId || (string) $s['code'] === (string) $stageId);
        }
        $authorizedUserIds = bouncer()->getAuthorizedUserIds();

        $data = [];

        foreach ($stages as $stage) {
            $query = $this->taskRepository->getModel()->newQuery()
                ->with(['assignedUser', 'creator', 'entity'])
                ->where('tasks.status', $stage['code']);

            if ($authorizedUserIds !== null) {
                $query->where(function ($q) use ($authorizedUserIds) {
                    $q->whereIn('tasks.assigned_to', $authorizedUserIds)
                        ->orWhereIn('tasks.created_by', $authorizedUserIds);
                });
            }

            if (request()->filled('assigned_user_id')) {
                $assignedUserId = request()->query('assigned_user_id');

                if ((string) $assignedUserId === '0') {
                    $query->whereNull('tasks.assigned_to');
                } else {
                    $query->where('tasks.assigned_to', (int) $assignedUserId);
                }
            }

            if (request()->filled('priority')) {
                $priority = request()->query('priority');

                if (is_array($priority)) {
                    $query->whereIn('tasks.priority', $priority);
                } else {
                    $query->where('tasks.priority', $priority);
                }
            }

            if (request()->filled('search')) {
                $search = request()->query('search');

                if (str_contains($search, ':')) {
                    $pairs = array_filter(explode(';', $search));

                    foreach ($pairs as $pair) {
                        if (str_contains($pair, ':')) {
                            [$field, $val] = explode(':', $pair, 2);

                            if ($field === 'title' || $field === 'all') {
                                $query->where(function ($q) use ($val) {
                                    $q->where('tasks.title', 'LIKE', '%'.$val.'%')
                                        ->orWhere('tasks.description', 'LIKE', '%'.$val.'%');
                                });
                            } elseif ($field === 'priority') {
                                $query->whereIn('tasks.priority', explode(',', $val));
                            } elseif ($field === 'assigned_to' || $field === 'user_id') {
                                $query->whereIn('tasks.assigned_to', explode(',', $val));
                            }
                        }
                    }
                } else {
                    $val = trim($search);

                    $query->where(function ($q) use ($val) {
                        $q->where('tasks.title', 'LIKE', '%'.$val.'%')
                            ->orWhere('tasks.description', 'LIKE', '%'.$val.'%');
                    });
                }
            }

            $this->applyDateRangeFilters($query);

            $limit = min(50, max(1, (int) request()->query('limit', 10)));

            $paginator = $query->orderBy('tasks.due_date', 'asc')
                ->orderBy('tasks.created_at', 'desc')
                ->paginate($limit);

            $data[$stage['code']] = [
                'id' => $stage['id'],
                'code' => $stage['code'],
                'name' => $stage['name'],
                'color' => $stage['color'],
                'sort_order' => $stage['sort_order'],
                'is_default' => $stage['is_default'] ?? false,
                'tasks' => [
                    'data' => TaskResource::collection($paginator->items()),
                    'meta' => [
                        'current_page' => $paginator->currentPage(),
                        'from' => $paginator->firstItem(),
                        'last_page' => $paginator->lastPage(),
                        'per_page' => $paginator->perPage(),
                        'to' => $paginator->lastItem(),
                        'total' => $paginator->total(),
                    ],
                ],
            ];
        }

        return response()->json($data);
    }

    /**
     * Update task stage/status when dragged in Kanban board.
     */
    public function updateStage(int $id): JsonResponse
    {
        $status = request('status') ?? request('lead_pipeline_stage_id');

        $task = $this->taskRepository->findOrFail($id);

        $this->authorizeTaskAccess($task);

        if (! in_array($status, $this->getValidStatusList(), true)) {
            return response()->json(['message' => 'Invalid status provided.'], 422);
        }

        $updated = $this->taskRepository->updateStatus($task, $status);

        return response()->json([
            'message' => trans('task::app.status-updated'),
            'data' => new TaskResource($updated),
        ]);
    }

    /**
     * Update task attributes quickly from quick-view drawer.
     */
    public function quickUpdate(int $id): JsonResponse
    {
        $task = $this->taskRepository->findOrFail($id);

        $this->authorizeTaskAccess($task);

        $data = $this->validate(request(), [
            'title' => 'sometimes|required|string|max:255',
            'assigned_to' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::in($this->getAssignableUserIds()),
            ],
            'status' => ['sometimes', 'required', Rule::in($this->getValidStatusList())],
            'priority' => 'sometimes|required|in:low,medium,high,urgent',
            'due_date' => 'sometimes|nullable|date',
            'due_time' => 'sometimes|nullable',
            'description' => 'sometimes|nullable|string',
        ]);

        $updated = $this->taskRepository->update($data, $id);

        return response()->json([
            'message' => trans('task::app.update-success'),
            'data' => new TaskResource($updated),
        ]);
    }

    /**
     * Get tasks structured by pipeline stages for the drag-and-drop Kanban view.
     */
    public function kanbanData(): JsonResponse
    {
        $userIds = bouncer()->hasPermission('tasks') && auth()->guard('user')->user()->view_permission !== 'global'
            ? bouncer()->getAuthorizedUserIds()
            : null;

        $filters = [
            'search' => request('search'),
            'user_id' => request('user_id'),
            'priority' => request('priority'),
            'view' => request('view'),
        ];

        $stages = $this->taskRepository->getKanbanStages($filters, $userIds);
        $stats = $this->taskRepository->getTaskStats($userIds ? auth()->id() : null);

        return response()->json([
            'stages' => $stages,
            'stats' => $stats,
        ]);
    }

    /**
     * Show the form for creating a new task.
     */
    public function create(): View
    {
        $users = $this->userRepository->scopeToWorkspace()->all();

        $entityType = old('entity_type', request()->get('entity_type'));
        $entityId = old('entity_id', request()->get('entity_id'));
        $entityTitle = null;

        if ($entityType && $entityId) {
            $prefillEntity = $this->resolveEntity($entityType, (int) $entityId);

            $entityTitle = $prefillEntity
                ? ($prefillEntity->title ?? $prefillEntity->name ?? $prefillEntity->subject ?? ('#'.$prefillEntity->id))
                : ('#'.$entityId);
        }

        $stages = $this->getStagesList();

        return view('task::create', compact('users', 'entityType', 'entityId', 'entityTitle', 'stages'));
    }

    /**
     * Store a newly created task.
     */
    public function store(TaskRequest $request): JsonResponse|RedirectResponse
    {
        $data = $request->validated();

        $task = $this->taskRepository->create($data);

        // Optional CRM activity timeline integration
        $this->logActivityForEntity($task, 'created');

        if ($request->ajax()) {
            return response()->json([
                'message' => trans('admin::app.tasks.create-success'),
                'task' => $task,
            ]);
        }

        session()->flash('success', trans('admin::app.tasks.create-success'));

        return redirect()->route('admin.tasks.index');
    }

    /**
     * Search CRM entities for task relationship dropdown.
     */
    public function searchEntities(Request $request): JsonResponse
    {
        $entityType = (string) $request->get('entity_type');
        $query = trim((string) $request->get('query'));
        $limit = min(50, max(1, (int) $request->get('limit', 20)));

        if (! in_array($entityType, ['leads', 'persons', 'organizations', 'quotes'], true)) {
            return response()->json(['data' => []]);
        }

        $authorizedUserIds = bouncer()->getAuthorizedUserIds();

        $results = match ($entityType) {
            'leads' => $this->searchLeads($authorizedUserIds, $query, $limit),
            'persons' => $this->searchPersons($authorizedUserIds, $query, $limit),
            'organizations' => $this->searchOrganizations($authorizedUserIds, $query, $limit),
            'quotes' => $this->searchQuotes($authorizedUserIds, $query, $limit),
            default => collect(),
        };

        return response()->json(['data' => $results]);
    }

    /**
     * Display the specified task details.
     */
    public function view(int $id): View
    {
        $task = $this->taskRepository->findOrFail($id);

        $this->authorizeTaskAccess($task);

        return view('task::view', compact('task'));
    }

    /**
     * Show the form for editing the specified task.
     */
    public function edit(int $id): View
    {
        $task = $this->taskRepository->findOrFail($id);

        $this->authorizeTaskAccess($task);

        $users = $this->userRepository->scopeToWorkspace()->all();

        $entityType = old('entity_type', $task->entity_type);
        $entityId = old('entity_id', $task->entity_id);
        $entityTitle = null;

        if ($entityType && $entityId) {
            if ($entityType === $task->entity_type && (int) $entityId === (int) $task->entity_id) {
                $entityTitle = $task->entity_title ?: ('#'.$task->entity_id);
            } else {
                $resolved = $this->resolveEntity($entityType, (int) $entityId);
                $entityTitle = $resolved
                    ? ($resolved->title ?? $resolved->name ?? $resolved->subject ?? ('#'.$resolved->id))
                    : ('#'.$entityId);
            }
        }

        $stages = $this->getStagesList();

        return view('task::edit', compact('task', 'users', 'entityType', 'entityId', 'entityTitle', 'stages'));
    }

    /**
     * Update the specified task.
     */
    public function update(TaskRequest $request, int $id): JsonResponse|RedirectResponse
    {
        $task = $this->taskRepository->findOrFail($id);

        $this->authorizeTaskAccess($task);

        $data = $request->validated();

        $updated = $this->taskRepository->update($data, $id);

        if ($request->ajax()) {
            return response()->json([
                'message' => trans('admin::app.tasks.update-success'),
                'task' => $updated,
            ]);
        }

        session()->flash('success', trans('admin::app.tasks.update-success'));

        return redirect()->route('admin.tasks.index');
    }

    /**
     * Update status quickly (Complete, In Progress, Pending, Cancelled).
     */
    public function updateStatus(UpdateStatusRequest $request, int $id): JsonResponse
    {
        $task = $this->taskRepository->findOrFail($id);

        $this->authorizeTaskAccess($task);

        $newStatus = $request->input('status');

        $updated = $this->taskRepository->updateStatus($task, $newStatus);

        if ($newStatus === Task::STATUS_COMPLETED) {
            $this->logActivityForEntity($updated, 'completed');
        }

        return response()->json([
            'success' => true,
            'message' => trans('admin::app.tasks.status-updated'),
            'task' => $updated,
        ]);
    }

    /**
     * Remove the specified task.
     */
    public function destroy(int $id): JsonResponse|RedirectResponse
    {
        $task = $this->taskRepository->findOrFail($id);

        $this->authorizeTaskAccess($task);

        $this->taskRepository->delete($id);

        if (request()->ajax()) {
            return response()->json([
                'message' => trans('admin::app.tasks.destroy-success'),
            ]);
        }

        session()->flash('success', trans('admin::app.tasks.destroy-success'));

        return redirect()->route('admin.tasks.index');
    }

    /**
     * Mass update task status.
     */
    public function massUpdate(Request $request): JsonResponse
    {
        $indices = $request->input('indices', []);
        $value = $request->input('value');

        if (empty($indices) || ! in_array($value, [
            Task::STATUS_PENDING,
            Task::STATUS_IN_PROGRESS,
            Task::STATUS_COMPLETED,
            Task::STATUS_CANCELLED,
        ], true)) {
            return response()->json(['message' => trans('admin::app.tasks.invalid-data')], 400);
        }

        foreach ($indices as $id) {
            $task = $this->taskRepository->find($id);
            if ($task && $this->canAccessTask($task)) {
                $this->taskRepository->updateStatus($task, $value);
            }
        }

        return response()->json([
            'message' => trans('admin::app.tasks.mass-update-success'),
        ]);
    }

    /**
     * Mass delete tasks.
     */
    public function massDestroy(Request $request): JsonResponse
    {
        $indices = $request->input('indices', []);

        foreach ($indices as $id) {
            $task = $this->taskRepository->find($id);
            if ($task && $this->canAccessTask($task)) {
                $this->taskRepository->delete($id);
            }
        }

        return response()->json([
            'message' => trans('admin::app.tasks.mass-destroy-success'),
        ]);
    }

    /**
     * Get tasks related to a specific CRM entity for embedding in Lead/Person views.
     */
    public function entityTasks(string $entityType, int $entityId): JsonResponse
    {
        // Validate entity belongs to the active tenant
        $entity = $this->resolveEntity($entityType, $entityId);

        if (! $entity) {
            return response()->json(['message' => 'Entity not found'], 404);
        }

        $tasks = $this->taskRepository->getForEntity($entityType, $entityId);

        return response()->json([
            'tasks' => $tasks,
        ]);
    }

    /**
     * Get dashboard tasks statistics and widget data.
     */
    public function dashboardStats(): JsonResponse
    {
        $userId = auth()->id();

        $today = $this->taskRepository->getTodayTasks($userId, 5);
        $overdue = $this->taskRepository->getOverdueTasks($userId, 5);
        $pending = $this->taskRepository->getPendingTasks($userId, 5);
        $completed = $this->taskRepository->getRecentlyCompletedTasks($userId, 5);
        $stats = $this->taskRepository->getTaskStats($userId);

        return response()->json([
            'stats' => $stats,
            'today' => $today,
            'overdue' => $overdue,
            'pending' => $pending,
            'completed' => $completed,
        ]);
    }

    /**
     * Check if current user is authorized to interact with this task based on view permission.
     */
    protected function authorizeTaskAccess(Task $task): void
    {
        if (! $this->canAccessTask($task)) {
            abort(403, trans('admin::app.tasks.unauthorized'));
        }
    }

    protected function canAccessTask(Task $task): bool
    {
        $authorizedUserIds = bouncer()->getAuthorizedUserIds();

        if ($authorizedUserIds === null) {
            return true;
        }

        return in_array($task->assigned_to, $authorizedUserIds, true)
            || in_array($task->created_by, $authorizedUserIds, true);
    }

    /**
     * Resolve CRM entity inside active tenant.
     */
    protected function resolveEntity(string $entityType, int $entityId): ?object
    {

        $record = match ($entityType) {
            'leads' => $this->leadRepository->find($entityId),
            'persons' => $this->personRepository->find($entityId),
            'organizations' => $this->organizationRepository->find($entityId),
            'quotes' => $this->quoteRepository->find($entityId),
            default => null,
        };

        if ($record) {
            return $record;
        }

        return null;
    }

    /**
     * Optionally log activity on entity timeline when task is created or completed.
     */
    protected function logActivityForEntity(Task $task, string $action): void
    {
        if (! $task->entity_type || ! $task->entity_id) {
            return;
        }

        $entity = $this->resolveEntity($task->entity_type, $task->entity_id);

        if (! $entity || ! method_exists($entity, 'activities')) {
            return;
        }

        $title = $action === 'completed'
            ? trans('admin::app.tasks.timeline.completed', ['title' => $task->title])
            : trans('admin::app.tasks.timeline.created', ['title' => $task->title]);

        try {
            $activity = app(ActivityRepository::class)->create([
                'type' => 'system',
                'title' => $title,
                'is_done' => 1,
                'user_id' => auth()->id(),
            ]);

            $entity->activities()->attach($activity->id);
        } catch (\Throwable) {
            // Silently ignore timeline attachment failure to not break main task workflow
        }
    }

    /**
     * Search leads matching term or return recent records.
     */
    protected function searchLeads(?array $authorizedUserIds, string $query, int $limit)
    {
        $builder = Lead::query();

        if ($authorizedUserIds) {
            $builder->whereIn('user_id', $authorizedUserIds);
        }

        if ($query !== '') {
            $builder->where(function ($q) use ($query) {
                $q->where('title', 'like', '%'.$query.'%');

                if (is_numeric($query)) {
                    $q->orWhere('id', (int) $query);
                }
            });
        }

        return $builder->orderBy('id', 'desc')
            ->limit($limit)
            ->get(['id', 'title', 'lead_value'])
            ->map(fn ($lead) => [
                'id' => $lead->id,
                'name' => $lead->title,
                'sub_title' => $lead->lead_value ? ('Value: '.core()->formatBasePrice($lead->lead_value)) : null,
            ]);
    }

    /**
     * Search person contacts matching term or return recent records.
     */
    protected function searchPersons(?array $authorizedUserIds, string $query, int $limit)
    {
        $builder = Person::query();

        if ($authorizedUserIds) {
            $builder->whereIn('user_id', $authorizedUserIds);
        }

        if ($query !== '') {
            $builder->where(function ($q) use ($query) {
                $q->where('name', 'like', '%'.$query.'%')
                    ->orWhere('emails', 'like', '%'.$query.'%')
                    ->orWhere('contact_numbers', 'like', '%'.$query.'%');

                if (is_numeric($query)) {
                    $q->orWhere('id', (int) $query);
                }
            });
        }

        return $builder->orderBy('id', 'desc')
            ->limit($limit)
            ->get(['id', 'name', 'emails'])
            ->map(function ($person) {
                $email = null;

                if (! empty($person->emails)) {
                    $email = is_array($person->emails)
                        ? ($person->emails[0]['value'] ?? null)
                        : $person->emails;
                }

                return [
                    'id' => $person->id,
                    'name' => $person->name,
                    'sub_title' => $email ? ('Email: '.$email) : null,
                ];
            });
    }

    /**
     * Search organizations matching term or return recent records.
     */
    protected function searchOrganizations(?array $authorizedUserIds, string $query, int $limit)
    {
        $builder = Organization::query();

        if ($authorizedUserIds) {
            $builder->whereIn('user_id', $authorizedUserIds);
        }

        if ($query !== '') {
            $builder->where(function ($q) use ($query) {
                $q->where('name', 'like', '%'.$query.'%');

                if (is_numeric($query)) {
                    $q->orWhere('id', (int) $query);
                }
            });
        }

        return $builder->orderBy('id', 'desc')
            ->limit($limit)
            ->get(['id', 'name', 'address'])
            ->map(function ($org) {
                $city = is_array($org->address) ? ($org->address['city'] ?? null) : null;

                return [
                    'id' => $org->id,
                    'name' => $org->name,
                    'sub_title' => $city ? ('City: '.$city) : null,
                ];
            });
    }

    /**
     * Search quotes matching term or return recent records.
     */
    protected function searchQuotes(?array $authorizedUserIds, string $query, int $limit)
    {
        $builder = Quote::query();

        if ($authorizedUserIds) {
            $builder->whereIn('user_id', $authorizedUserIds);
        }

        if ($query !== '') {
            $builder->where(function ($q) use ($query) {
                $q->where('subject', 'like', '%'.$query.'%');

                if (is_numeric($query)) {
                    $q->orWhere('id', (int) $query);
                }
            });
        }

        return $builder->orderBy('id', 'desc')
            ->limit($limit)
            ->get(['id', 'subject', 'grand_total'])
            ->map(fn ($quote) => [
                'id' => $quote->id,
                'name' => $quote->subject,
                'sub_title' => $quote->grand_total ? ('Total: '.core()->formatBasePrice($quote->grand_total)) : null,
            ]);
    }

    /**
     * Kanban lookup for filterable columns (e.g., assigned_to).
     */
    public function kanbanLookup()
    {
        $params = $this->validate(request(), [
            'column' => ['required'],
            'search' => ['required', 'min:2'],
        ]);

        $column = collect($this->getKanbanColumns())->where('index', $params['column'])->firstOrFail();

        $query = app($column['filterable_options']['repository'])
            ->select([$column['filterable_options']['column']['label'].' as label', $column['filterable_options']['column']['value'].' as value'])
            ->where($column['filterable_options']['column']['label'], 'LIKE', '%'.$params['search'].'%');

        if ($params['column'] === 'assigned_to') {
            $assignableIds = $this->getAssignableUserIds();

            if ($assignableIds === []) {
                return [];
            }

            $query->whereIn($column['filterable_options']['column']['value'], $assignableIds);
        }

        return $query
            ->get()
            ->map
            ->only('label', 'value');
    }

    /**
     * Get the defined workflow stages for tasks.
     *
     * @return array<int, array<string, mixed>>
     */
    /**
     * Get stages list for Task pipeline.
     */
    public function getStages(): JsonResponse
    {
        return response()->json([
            'stages' => $this->getStagesList(),
        ]);
    }

    /**
     * Store a new custom list stage for the task pipeline.
     */
    public function storeStage(Request $request): JsonResponse
    {
        $validated = $this->validate($request, [
            'name' => 'required|string|max:100',
            'color' => 'nullable|string|max:20',
        ]);

        $name = trim($validated['name']);
        $code = Str::slug($name, '_');

        if (empty($code)) {
            $code = 'stage_'.time();
        }

        /*
         * Provision the standard workflow columns first: creating a list on a workspace
         * whose stages were not persisted yet used to leave the new column as the only
         * one, hiding Pending, In Progress, Completed and Cancelled from the board.
         */
        $this->taskStageRepository->ensureDefaultStages();

        $baseCode = $code;
        $counter = 1;

        while ($this->taskStageRepository->getModel()->newQuery()
            ->where('code', $code)
            ->exists()) {
            $code = $baseCode.'_'.$counter++;
        }

        $stage = $this->taskStageRepository->create([
            'code' => $code,
            'name' => $name,
            'color' => ! empty($validated['color']) ? $validated['color'] : '#3B82F6',
            'sort_order' => $this->taskStageRepository->getNextSortOrder(),
            'is_default' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => trans('task::app.kanban.stage-create-success', ['default' => 'List column created successfully.']),
            'stage' => [
                'id' => $stage->code,
                'code' => $stage->code,
                'name' => $stage->name,
                'color' => $stage->color,
                'sort_order' => $stage->sort_order,
                'stage_id' => $stage->id,
            ],
            'stages' => $this->getStagesList(),
        ]);
    }

    /**
     * Update a custom list in the active workspace.
     */
    public function updateStageDefinition(Request $request, string $code): JsonResponse
    {
        $validated = $this->validate($request, [
            'name' => 'required|string|max:100',
            'color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        $validated['name'] = trim($validated['name']);

        if ($validated['name'] === '') {
            return response()->json(['message' => trans('admin::app.tasks.list-name-required')], 422);
        }

        $stage = $this->findCustomStage($code);

        $stage->update([
            'name' => trim($validated['name']),
            'color' => $validated['color'] ?? $stage->color,
        ]);

        return response()->json([
            'success' => true,
            'message' => trans('admin::app.tasks.list-updated'),
            'stages' => $this->getStagesList(),
        ]);
    }

    /**
     * Move all tasks from a custom list to another list.
     */
    public function reassignTasks(Request $request, string $code): JsonResponse
    {
        $stage = $this->findCustomStage($code);
        $validated = $this->validate($request, [
            'destination_stage' => [
                'required',
                'string',
                Rule::in($this->taskStageRepository->getStagesForCurrentTenant()
                    ->reject(fn ($candidate) => $candidate->code === $stage->code)
                    ->pluck('code')
                    ->all()),
            ],
        ]);

        DB::transaction(function () use ($stage, $validated) {
            Task::query()
                ->where('status', $stage->code)
                ->chunkById(100, function ($tasks) use ($validated) {
                    foreach ($tasks as $task) {
                        $this->taskRepository->updateStatus($task, $validated['destination_stage']);
                    }
                });
        });

        return response()->json([
            'success' => true,
            'message' => trans('admin::app.tasks.list-tasks-reassigned'),
            'stages' => $this->getStagesList(),
        ]);
    }

    /**
     * Delete a custom list and either remove or reassign its tasks.
     */
    public function destroyStage(Request $request, string $code): JsonResponse
    {
        $stage = $this->findCustomStage($code);
        $validated = $this->validate($request, [
            'action' => ['required', Rule::in(['delete_tasks', 'reassign_tasks'])],
            'destination_stage' => [
                'required_if:action,reassign_tasks',
                'nullable',
                'string',
                Rule::in($this->taskStageRepository->getStagesForCurrentTenant()
                    ->reject(fn ($candidate) => $candidate->code === $stage->code)
                    ->pluck('code')
                    ->all()),
            ],
        ]);

        DB::transaction(function () use ($stage, $validated) {
            Task::query()
                ->where('status', $stage->code)
                ->chunkById(100, function ($tasks) use ($validated) {
                    foreach ($tasks as $task) {
                        if ($validated['action'] === 'delete_tasks') {
                            $this->taskRepository->delete($task->id);

                            continue;
                        }

                        $this->taskRepository->updateStatus($task, $validated['destination_stage']);
                    }
                });

            $stage->delete();
        });

        return response()->json([
            'success' => true,
            'message' => trans('admin::app.tasks.'.($validated['action'] === 'delete_tasks'
                ? 'list-deleted-with-tasks'
                : 'list-deleted-moved')),
            'stages' => $this->getStagesList(),
        ]);
    }

    /**
     * Find a custom stage within the active workspace.
     */
    protected function findCustomStage(string $code)
    {
        $stage = $this->taskStageRepository->getModel()->newQuery()
            ->where('code', $code)
            ->where('is_default', false)
            ->first();

        abort_unless($stage, 404);

        return $stage;
    }

    /**
     * Get all valid status codes currently recognized for the workspace.
     *
     * @return array<int, string>
     */
    protected function getValidStatusList(): array
    {
        $standard = [
            Task::STATUS_PENDING,
            Task::STATUS_IN_PROGRESS,
            Task::STATUS_COMPLETED,
            Task::STATUS_CANCELLED,
        ];

        $stageCodes = collect($this->getStagesList())->pluck('code')->all();

        return array_values(array_unique(array_merge($standard, $stageCodes)));
    }

    /**
     * Get the defined workflow stages for tasks.
     */
    protected function getStagesList(): array
    {
        try {
            $stages = $this->taskStageRepository->getStagesForCurrentTenant();

            if ($stages->isNotEmpty()) {
                return $stages->map(fn ($stage) => [
                    'id' => $stage->code,
                    'code' => $stage->code,
                    'name' => $stage->name,
                    'color' => $stage->color ?: '#3B82F6',
                    'sort_order' => (int) $stage->sort_order,
                    'stage_id' => $stage->id,
                    'is_default' => (bool) $stage->is_default,
                ])->values()->all();
            }
        } catch (\Throwable) {
            // Fallback to static stages if database table is not migrated yet
        }

        return [
            [
                'id' => Task::STATUS_PENDING,
                'code' => Task::STATUS_PENDING,
                'name' => trans('task::app.status.pending'),
                'color' => '#F59E0B',
                'sort_order' => 1,
                'is_default' => true,
            ],
            [
                'id' => Task::STATUS_IN_PROGRESS,
                'code' => Task::STATUS_IN_PROGRESS,
                'name' => trans('task::app.status.in_progress'),
                'color' => '#3B82F6',
                'sort_order' => 2,
                'is_default' => true,
            ],
            [
                'id' => Task::STATUS_COMPLETED,
                'code' => Task::STATUS_COMPLETED,
                'name' => trans('task::app.status.completed'),
                'color' => '#10B981',
                'sort_order' => 3,
                'is_default' => true,
            ],
            [
                'id' => Task::STATUS_CANCELLED,
                'code' => Task::STATUS_CANCELLED,
                'name' => trans('task::app.status.cancelled'),
                'color' => '#6B7280',
                'sort_order' => 4,
                'is_default' => true,
            ],
        ];
    }

    /**
     * Active workspace member user IDs.
     *
     * @return array<int, int>
     */
    private function getAssignableUserIds(): array
    {
        $workspaceUserIds = DB::table('users')
            ->where('status', 1)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($authorizedIds = bouncer()->getAuthorizedUserIds()) {
            $workspaceUserIds = array_values(array_intersect($workspaceUserIds, array_map('intval', $authorizedIds)));
        }

        return $workspaceUserIds;
    }

    /**
     * Users available for task pipeline assignee filter / quick-view assignment.
     *
     * @return Collection<int, array{id:int,name:string}>
     */
    private function getAssignableUsers(): Collection
    {
        $userIds = $this->getAssignableUserIds();

        if ($userIds === []) {
            return collect();
        }

        return $this->userRepository->getModel()
            ->newQuery()
            ->whereIn('id', $userIds)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($user) => [
                'id' => (int) $user->id,
                'name' => $user->name,
            ])
            ->values();
    }

    /**
     * Apply date range filters to task query builder.
     */
    private function applyDateRangeFilters($query): void
    {
        foreach ($this->getKanbanDateColumns() as $index => $columnName) {
            $requestedDates = request($index);

            if (empty($requestedDates)) {
                continue;
            }

            $column = new DateColumn([
                'index' => $index,
                'label' => $index,
                'type' => 'date',
                'filterable' => true,
                'filterable_type' => 'date_range',
            ]);

            $column->setColumnName($columnName);

            $column->processFilter($query, $requestedDates);
        }
    }

    /**
     * Returns the kanban date columns, mapped to their qualified table column name.
     */
    private function getKanbanDateColumns(): array
    {
        return [
            'due_date' => 'tasks.due_date',
            'created_at' => 'tasks.created_at',
        ];
    }

    /**
     * Get filterable columns for the kanban filter drawer.
     */
    private function getKanbanColumns(): array
    {
        return [
            [
                'index' => 'id',
                'label' => trans('task::app.datagrid.id'),
                'type' => 'integer',
                'searchable' => false,
                'search_field' => 'in',
                'filterable' => true,
                'filterable_type' => null,
                'filterable_options' => [],
                'allow_multiple_values' => true,
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'index' => 'priority',
                'label' => trans('task::app.fields.priority'),
                'type' => 'string',
                'searchable' => false,
                'search_field' => 'in',
                'filterable' => true,
                'filterable_type' => 'dropdown',
                'filterable_options' => [
                    'options' => [
                        ['label' => trans('task::app.priority.urgent'), 'value' => 'urgent'],
                        ['label' => trans('task::app.priority.high'), 'value' => 'high'],
                        ['label' => trans('task::app.priority.medium'), 'value' => 'medium'],
                        ['label' => trans('task::app.priority.low'), 'value' => 'low'],
                    ],
                ],
                'allow_multiple_values' => true,
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'index' => 'assigned_to',
                'label' => trans('task::app.fields.assigned_to'),
                'type' => 'string',
                'searchable' => false,
                'search_field' => 'in',
                'filterable' => true,
                'filterable_type' => 'searchable_dropdown',
                'filterable_options' => [
                    'repository' => UserRepository::class,
                    'column' => [
                        'label' => 'name',
                        'value' => 'id',
                    ],
                ],
                'allow_multiple_values' => true,
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'index' => 'due_date',
                'label' => trans('task::app.fields.due_date'),
                'type' => 'date',
                'searchable' => false,
                'search_field' => 'between',
                'filterable' => true,
                'filterable_type' => 'date_range',
                'filterable_options' => [],
                'allow_multiple_values' => false,
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'index' => 'created_at',
                'label' => trans('task::app.fields.created_at'),
                'type' => 'date',
                'searchable' => false,
                'search_field' => 'between',
                'filterable' => true,
                'filterable_type' => 'date_range',
                'filterable_options' => [],
                'allow_multiple_values' => false,
                'sortable' => true,
                'visibility' => true,
            ],
        ];
    }
}
