<?php

namespace Webkul\Task\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Webkul\Task\Models\Task;

class UpdateStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

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
            'status' => [
                'required',
                'string',
                Rule::in($validStatuses),
            ],
        ];
    }
}
