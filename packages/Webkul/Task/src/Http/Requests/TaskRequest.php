<?php

namespace Webkul\Task\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Webkul\Contact\Models\Organization;
use Webkul\Contact\Models\Person;
use Webkul\Lead\Models\Lead;
use Webkul\Quote\Models\Quote;
use Webkul\Task\Models\Task;

class TaskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $validStatuses = [
            Task::STATUS_PENDING,
            Task::STATUS_IN_PROGRESS,
            Task::STATUS_COMPLETED,
            Task::STATUS_CANCELLED,
        ];

        try {
            if (Schema::hasTable('task_stages')) {
                $stageCodes = DB::table('task_stages')->pluck('code')->all();

                if (! empty($stageCodes)) {
                    $validStatuses = array_values(array_unique(array_merge($validStatuses, $stageCodes)));
                }
            }
        } catch (\Throwable) {
        }

        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => [
                'nullable',
                'string',
                Rule::in($validStatuses),
            ],
            'priority' => [
                'nullable',
                'string',
                Rule::in([
                    Task::PRIORITY_LOW,
                    Task::PRIORITY_MEDIUM,
                    Task::PRIORITY_HIGH,
                    Task::PRIORITY_URGENT,
                ]),
            ],
            'due_date' => 'nullable|date',
            'due_time' => 'nullable|string|max:10',
            'schedule_from' => 'nullable|date',
            'schedule_to' => 'nullable|date|after_or_equal:schedule_from',
            'location' => 'nullable|string|max:255',
            'reminder_minutes' => ['nullable', 'integer', Rule::in([5, 15, 30, 60, 1440])],
            'assigned_to' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('status', 1)),
            ],
            'participants' => 'sometimes|array',
            'participants.users' => 'sometimes|array',
            'participants.users.*' => [
                'integer',
                'distinct',
                Rule::in($this->assignableUserIds()),
            ],
            'participants.persons' => 'sometimes|array',
            'participants.persons.*' => [
                'integer',
                'distinct',
                Rule::exists('persons', 'id'),
            ],
            'participants_present' => 'sometimes|boolean',
            'entity_type' => [
                'nullable',
                'string',
                Rule::in(['leads', 'persons', 'organizations', 'quotes']),
            ],
            'entity_id' => [
                'nullable',
                'required_with:entity_type',
                'integer',
            ],
        ];
    }

    /**
     * Configure the validator instance with entity existence checks in the current tenant DB.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            if ($assignedTo = $this->input('assigned_to')) {
                $userIsActive = DB::table('users')
                    ->where('id', $assignedTo)
                    ->where('status', 1)
                    ->exists();

                if (! $userIsActive) {
                    $validator->errors()->add('assigned_to', trans('admin::app.tasks.validation.user-inactive'));
                }
            }

            $authorizedUserIds = $this->authorizedUserIds();

            if ($authorizedUserIds !== null) {
                $unauthorizedPersonIds = collect($this->input('participants.persons', []))
                    ->filter(fn ($personId) => ! Person::query()
                        ->whereKey($personId)
                        ->whereIn('user_id', $authorizedUserIds)
                        ->exists());

                foreach ($unauthorizedPersonIds as $personId) {
                    $validator->errors()->add(
                        'participants.persons',
                        trans('task::app.validation.participant-invalid')
                    );
                }
            }

            $entityType = $this->input('entity_type');
            $entityId = $this->input('entity_id');

            if ($entityType && $entityId) {
                $modelClass = match ($entityType) {
                    'leads' => Lead::class,
                    'persons' => Person::class,
                    'organizations' => Organization::class,
                    'quotes' => Quote::class,
                    default => null,
                };

                if ($modelClass && ! $modelClass::find($entityId)) {
                    $validator->errors()->add('entity_id', trans('admin::app.tasks.validation.entity-invalid'));
                }
            }
        });
    }

    /**
     * @return array<int, int>
     */
    private function assignableUserIds(): array
    {
        $userIds = DB::table('users')
            ->where('status', 1)
            ->pluck('id')
            ->map(fn ($id) => (int) $id);

        $authorizedUserIds = $this->authorizedUserIds();

        if ($authorizedUserIds !== null) {
            $userIds = $userIds->intersect(array_map('intval', $authorizedUserIds));
        }

        return $userIds->values()->all();
    }

    /**
     * Unauthenticated request validation is used by unit tests and service callers.
     */
    private function authorizedUserIds(): ?array
    {
        if (! auth()->guard('user')->check()) {
            return null;
        }

        return bouncer()->getAuthorizedUserIds();
    }
}
