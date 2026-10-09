<?php

namespace Webkul\Admin\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeadResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request
     * @return array
     */
    public function toArray($request)
    {
        $leadValue = $this->lead_value;

        if (is_numeric($leadValue)) {
            $leadValue = number_format((float) $leadValue, 2, '.', '');
        }

        $nextActivity = null;

        if ($this->relationLoaded('activities')) {
            $activity = $this->activities
                ->where('is_done', 0)
                ->sortBy(fn ($item) => $item->schedule_from?->timestamp ?? PHP_INT_MAX)
                ->first();

            if ($activity) {
                $nextActivity = [
                    'id' => $activity->id,
                    'title' => $activity->title,
                    'type' => $activity->type,
                    'schedule_from' => $activity->schedule_from,
                    'schedule_to' => $activity->schedule_to,
                ];
            }
        }

        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'lead_value' => $leadValue,
            'formatted_lead_value' => $leadValue,
            'status' => $this->status,
            'user_id' => $this->user_id,
            'lead_pipeline_stage_id' => $this->lead_pipeline_stage_id,
            'expected_close_date' => $this->expected_close_date,
            'rotten_days' => $this->rotten_days,
            'closed_at' => $this->closed_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'person' => $this->person ? new PersonResource($this->person) : null,
            'user' => $this->user ? new UserResource($this->user) : null,
            'type' => $this->type ? new TypeResource($this->type) : null,
            'source' => $this->source ? new SourceResource($this->source) : null,
            'pipeline' => $this->pipeline ? new PipelineResource($this->pipeline) : null,
            'stage' => $this->stage ? new StageResource($this->stage) : null,
            'tags' => TagResource::collection($this->tags),
            'next_activity' => $nextActivity,
        ];
    }
}
