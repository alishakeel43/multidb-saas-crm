<?php

namespace Webkul\Task\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $dueDateFormatted = null;
        if ($this->due_date) {
            try {
                $dueDateFormatted = Carbon::parse($this->due_date)->isoFormat('MMM D, YYYY');
            } catch (\Throwable) {
                $dueDateFormatted = (string) $this->due_date;
            }
        }

        $isOverdue = (bool) $this->is_overdue;
        $overdueDays = 0;
        if ($isOverdue && $this->due_date) {
            try {
                $overdueDays = (int) Carbon::now()->diffInDays(Carbon::parse($this->due_date));
            } catch (\Throwable) {
                $overdueDays = 1;
            }
        }

        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'status_label' => trans('task::app.status.'.$this->status),
            'priority' => $this->priority,
            'priority_label' => trans('task::app.priority.'.$this->priority),
            'due_date' => $this->due_date ? Carbon::parse($this->due_date)->format('Y-m-d') : null,
            'due_time' => $this->due_time,
            'formatted_due_date' => $dueDateFormatted,
            'is_overdue' => $isOverdue,
            'overdue_days' => $overdueDays,
            'assigned_to' => $this->assigned_to,
            'assigned_user' => $this->assignedUser ? [
                'id' => (int) $this->assignedUser->id,
                'name' => $this->assignedUser->name,
            ] : null,
            'entity_type' => $this->entity_type,
            'entity_id' => $this->entity_id,
            'entity_title' => $this->entity_title,
            'entity_url' => $this->entity_url,
            'view_url' => route('admin.tasks.view', $this->id),
            'edit_url' => route('admin.tasks.edit', $this->id),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
