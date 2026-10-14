<?php

namespace Webkul\Task\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Webkul\Task\Models\Task;

class TaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => [$this->isMethod('POST') ? 'required' : 'sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'max:50'],
            'priority' => ['nullable', 'string', 'in:'.implode(',', Task::PRIORITIES)],
            'due_date' => ['nullable', 'date'],
            'due_time' => ['nullable', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'assigned_to' => ['nullable', 'integer'],
            'entity_type' => ['nullable', 'string', 'in:'.implode(',', array_keys(Task::ENTITIES))],
            'entity_id' => ['nullable', 'integer'],
        ];
    }
}
