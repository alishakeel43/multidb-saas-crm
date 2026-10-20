<?php

namespace Webkul\AiSales\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Validator;
use Webkul\Activity\Repositories\ActivityRepository;
use Webkul\Lead\Repositories\LeadRepository;
use Webkul\Lead\Repositories\PipelineRepository;
use Webkul\User\Repositories\UserRepository;

class AiSalesController extends Controller
{
    public function __construct(
        protected LeadRepository $leadRepository,
        protected PipelineRepository $pipelineRepository,
        protected ActivityRepository $activityRepository,
        protected UserRepository $userRepository,
    ) {}

    public function health(): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'service' => 'krayin-ai-sales',
            'tenant' => app('currentTenant')->id ?? null,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'company_name' => ['required_without:title', 'nullable', 'string', 'max:255'],
            'title' => ['required_without:company_name', 'nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:20000'],
            'research_summary' => ['nullable', 'string', 'max:20000'],
            'fit_score' => ['nullable', 'string', 'max:20'],
            'recommended_product' => ['nullable', 'string', 'max:255'],
            'personalization_notes' => ['nullable', 'string', 'max:20000'],
            'next_best_action' => ['nullable', 'string', 'max:2000'],
            'sequence_text' => ['nullable', 'string', 'max:50000'],
            'lead_source_id' => ['nullable', 'integer'],
            'user_id' => ['nullable', 'integer'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        $input = $validator->validated();

        $result = DB::transaction(function () use ($input) {
            $pipeline = $this->pipelineRepository->getDefaultPipeline();
            $stage = $pipeline->stages()->orderBy('sort_order')->firstOrFail();
            $owner = ! empty($input['user_id'])
                ? $this->userRepository->findOrFail($input['user_id'])
                : $this->userRepository->orderBy('id')->first();

            abort_unless($owner, 422, 'No CRM user is available to own the lead.');

            $notes = array_filter([
                $input['description'] ?? $input['research_summary'] ?? null,
                isset($input['fit_score']) ? 'Fit score: '.$input['fit_score'] : null,
                isset($input['recommended_product']) ? 'Recommended product: '.$input['recommended_product'] : null,
                isset($input['personalization_notes']) ? "Personalization notes:\n".$input['personalization_notes'] : null,
                isset($input['next_best_action']) ? 'Next best action: '.$input['next_best_action'] : null,
            ]);

            $leadData = [
                'entity_type' => 'leads',
                'title' => $input['title'] ?? $input['company_name'],
                'description' => implode("\n\n", $notes),
                'status' => 1,
                'user_id' => $owner->id,
                'lead_pipeline_id' => $pipeline->id,
                'lead_pipeline_stage_id' => $stage->id,
            ];

            if (! empty($input['lead_source_id'])) {
                $leadData['lead_source_id'] = $input['lead_source_id'];
            }

            if (! empty($input['contact_name']) || ! empty($input['email']) || ! empty($input['phone'])) {
                $leadData['person'] = [
                    'name' => $input['contact_name'] ?? $input['company_name'] ?? $input['title'],
                    'emails' => ! empty($input['email']) ? [['label' => 'work', 'value' => $input['email']]] : [],
                    'contact_numbers' => ! empty($input['phone']) ? [['label' => 'work', 'value' => $input['phone']]] : [],
                ];
            }

            Event::dispatch('lead.create.before');
            $lead = $this->leadRepository->create($leadData);
            Event::dispatch('lead.create.after', $lead);

            $activity = $this->activityRepository->create([
                'title' => 'APPROVE OUTREACH - '.$lead->title,
                'type' => 'todo',
                'comment' => $this->approvalText($input),
                'schedule_from' => Carbon::now(),
                'schedule_to' => Carbon::now()->addDay(),
                'is_done' => 0,
                'user_id' => $owner->id,
                'participants' => ['users' => [$owner->id]],
            ]);
            $activity->leads()->sync([$lead->id]);

            return ['lead' => $lead, 'activity' => $activity];
        });

        return response()->json([
            'message' => 'Lead and approval activity created.',
            'data' => [
                'id' => $result['lead']->id,
                'title' => $result['lead']->title,
                'approval_activity_id' => $result['activity']->id,
            ],
        ], 201);
    }

    public function destroy(int $id): JsonResponse
    {
        $lead = $this->leadRepository->findOrFail($id);
        $activityIds = $lead->activities()->pluck('activities.id');

        DB::transaction(function () use ($lead, $activityIds) {
            $lead->activities()->detach();
            $lead->delete();
            $this->activityRepository->findWhereIn('id', $activityIds->all())->each->delete();
        });

        return response()->json(['message' => 'Lead and linked integration activities deleted.']);
    }

    protected function approvalText(array $input): string
    {
        return implode("\n\n", array_filter([
            'Created by AI Sales Engine. Human approval is required before outreach.',
            isset($input['fit_score']) ? 'Fit score: '.$input['fit_score'] : null,
            isset($input['recommended_product']) ? 'Recommended product: '.$input['recommended_product'] : null,
            isset($input['personalization_notes']) ? "Personalization notes:\n".$input['personalization_notes'] : null,
            isset($input['sequence_text']) ? "Proposed sequence:\n".$input['sequence_text'] : null,
        ]));
    }
}
