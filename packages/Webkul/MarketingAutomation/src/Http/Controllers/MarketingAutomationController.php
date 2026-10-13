<?php

namespace Webkul\MarketingAutomation\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\MarketingAutomation\Models\Attribution;
use Webkul\MarketingAutomation\Models\Audience;
use Webkul\MarketingAutomation\Models\Campaign;
use Webkul\MarketingAutomation\Models\EmailEnrollment;
use Webkul\MarketingAutomation\Models\EmailMessage;
use Webkul\MarketingAutomation\Models\EmailSequence;
use Webkul\MarketingAutomation\Models\EmailStep;
use Webkul\MarketingAutomation\Models\EngagementEvent;
use Webkul\MarketingAutomation\Models\EngagementRule;
use Webkul\MarketingAutomation\Models\EngagementScore;
use Webkul\MarketingAutomation\Models\Workflow;
use Webkul\MarketingAutomation\Models\WorkflowAction;
use Webkul\MarketingAutomation\Repositories\AudienceRepository;
use Webkul\MarketingAutomation\Repositories\CampaignRepository;
use Webkul\MarketingAutomation\Repositories\EmailSequenceRepository;
use Webkul\MarketingAutomation\Repositories\EngagementRuleRepository;
use Webkul\MarketingAutomation\Repositories\WorkflowRepository;
use Webkul\MarketingAutomation\Services\CampaignReport;
use Webkul\MarketingAutomation\Services\SequenceRunner;
use Webkul\MarketingAutomation\Support\Catalog;

class MarketingAutomationController extends Controller
{
    public function __construct(
        private WorkflowRepository $workflows,
        private CampaignRepository $campaigns,
        private AudienceRepository $audiences,
        private EmailSequenceRepository $sequences,
        private EngagementRuleRepository $engagementRules,
        private CampaignReport $reports,
        private SequenceRunner $runner,
    ) {}

    public function index(): View
    {
        return view('marketing_automation::index', [
            'ready' => Schema::hasTable('ma_workflows'),
            'workflows' => Schema::hasTable('ma_workflows') ? Workflow::query()->where('is_active', true)->count() : 0,
            'campaigns' => Schema::hasTable('ma_campaigns') ? Campaign::query()->where('status', 'active')->count() : 0,
            'attributions' => Schema::hasTable('ma_attributions') ? Attribution::query()->whereNotNull('lead_id')->count() : 0,
            'sent' => Schema::hasTable('ma_email_messages') ? EmailMessage::query()->whereNotNull('sent_at')->count() : 0,
            'engagement' => Schema::hasTable('ma_engagement_scores') ? (int) EngagementScore::query()->sum('points') : 0,
        ]);
    }

    public function workflows(): View
    {
        return view('marketing_automation::workflows', [
            'workflows' => Workflow::query()->with('actions')->latest('id')->limit(100)->get(),
            'events' => Catalog::eventOptions(),
            'operators' => Catalog::OPERATORS,
            'attributes' => Catalog::ATTRIBUTES,
        ]);
    }

    public function storeWorkflow(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'event' => 'required|string',
            'condition_type' => 'required|in:and,or',
            'conditions' => 'array',
            'conditions.*.attribute' => 'nullable|string|max:64',
            'conditions.*.operator' => 'nullable|string|max:32',
            'conditions.*.value' => 'nullable|string|max:255',
            'actions' => 'array',
            'actions.*.branch' => 'nullable|in:when_match,when_else',
            'actions.*.type' => 'nullable|in:delay,task,notification,email,update',
            'actions.*.minutes' => 'nullable|integer|min:1|max:43200',
            'actions.*.title' => 'nullable|string|max:255',
            'actions.*.subject' => 'nullable|string|max:255',
            'actions.*.body' => 'nullable|string|max:5000',
            'actions.*.field' => 'nullable|string|max:64',
            'actions.*.value' => 'nullable|string|max:255',
            'actions.*.link_url' => 'nullable|url|max:2048',
            'actions.*.link_label' => 'nullable|string|max:255',
        ]);

        $parsed = Catalog::parseEvent($data['event']);

        if ($parsed === null) {
            return back()->withErrors(['event' => 'Choose a trigger.'])->withInput();
        }

        [$entity, $event] = $parsed;
        $conditions = $this->conditionsFor($entity, $data['conditions'] ?? []);
        $actions = $this->actionsFor($entity, $data['actions'] ?? []);

        if ($actions === []) {
            return back()->withErrors(['actions' => 'Add at least one action.'])->withInput();
        }

        $workflow = $this->workflows->create([
            'name' => $data['name'],
            'entity_type' => $entity,
            'event' => $event,
            'condition_type' => $data['condition_type'],
            'conditions' => $conditions,
            'is_active' => $request->boolean('is_active', true),
        ]);

        foreach ($actions as $index => $action) {
            WorkflowAction::query()->create([
                'workflow_id' => $workflow->id,
                'branch' => $action['branch'],
                'sort' => $index,
                'type' => $action['type'],
                'config' => $action['config'],
            ]);
        }

        return back()->with('success', 'Workflow saved.');
    }

    public function destroyWorkflow(int $id): RedirectResponse
    {
        $this->workflows->delete($id);

        return back()->with('success', 'Workflow deleted.');
    }

    public function campaigns(): View
    {
        return view('marketing_automation::campaigns', [
            'campaigns' => Campaign::query()->with('audience')->latest('id')->limit(100)->get(),
            'audiences' => Audience::query()->orderBy('name')->get(),
        ]);
    }

    public function storeCampaign(Request $request): RedirectResponse
    {
        $data = $this->campaignData($request);
        $campaign = $this->campaigns->create($data);

        return redirect()
            ->route('admin.marketing_automation.campaigns.show', $campaign->id)
            ->with('success', 'Campaign saved.');
    }

    public function showCampaign(int $id): View
    {
        $campaign = Campaign::query()->findOrFail($id);

        return view('marketing_automation::campaign', [
            'campaign' => $campaign,
            'audiences' => Audience::query()->orderBy('name')->get(),
            'report' => $this->reports->summarize($campaign),
        ]);
    }

    public function updateCampaign(Request $request, int $id): RedirectResponse
    {
        $this->campaigns->update($this->campaignData($request), $id);

        return back()->with('success', 'Campaign updated.');
    }

    public function destroyCampaign(int $id): RedirectResponse
    {
        $this->campaigns->delete($id);

        return redirect()->route('admin.marketing_automation.campaigns')->with('success', 'Campaign deleted.');
    }

    public function audiences(): View
    {
        return view('marketing_automation::audiences', [
            'audiences' => Audience::query()->latest('id')->limit(100)->get(),
            'fields' => Catalog::AUDIENCE_FIELDS,
            'operators' => Catalog::OPERATORS,
        ]);
    }

    public function storeAudience(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'rules' => 'array',
            'rules.*.attribute' => 'nullable|string|max:64',
            'rules.*.operator' => 'nullable|string|max:32',
            'rules.*.value' => 'nullable|string|max:255',
        ]);

        $this->audiences->create([
            'name' => $data['name'],
            'rules' => $this->audienceRules($data['rules'] ?? []),
        ]);

        return back()->with('success', 'Audience saved.');
    }

    public function destroyAudience(int $id): RedirectResponse
    {
        $this->audiences->delete($id);

        return back()->with('success', 'Audience deleted.');
    }

    public function sequences(): View
    {
        return view('marketing_automation::sequences', [
            'sequences' => EmailSequence::query()->with(['campaign', 'audience'])->latest('id')->limit(100)->get(),
            'campaigns' => Campaign::query()->orderBy('name')->get(),
            'audiences' => Audience::query()->orderBy('name')->get(),
        ]);
    }

    public function storeSequence(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'status' => 'required|in:draft,active,paused',
            'campaign_id' => 'nullable|integer|exists:ma_campaigns,id',
            'audience_id' => 'nullable|integer|exists:ma_audiences,id',
        ]);

        $sequence = $this->sequences->create($data);

        return redirect()
            ->route('admin.marketing_automation.sequences.show', $sequence->id)
            ->with('success', 'Sequence saved. Add steps, then enroll the audience.');
    }

    public function showSequence(int $id): View
    {
        $sequence = EmailSequence::query()->with(['steps', 'enrollments', 'campaign', 'audience'])->findOrFail($id);

        return view('marketing_automation::sequence', ['sequence' => $sequence]);
    }

    public function storeStep(Request $request, int $id): RedirectResponse
    {
        $sequence = EmailSequence::query()->findOrFail($id);
        $data = $request->validate([
            'delay_minutes' => 'required|integer|min:0|max:43200',
            'subject' => 'required|string|max:255',
            'body' => 'required|string|max:10000',
            'link_url' => 'nullable|url|max:2048',
            'link_label' => 'nullable|string|max:255',
        ]);

        EmailStep::query()->create([
            ...$data,
            'sequence_id' => $sequence->id,
            'sort' => (int) $sequence->steps()->max('sort') + 1,
        ]);

        return back()->with('success', 'Step added.');
    }

    public function destroyStep(int $id, int $stepId): RedirectResponse
    {
        EmailStep::query()->where('sequence_id', $id)->whereKey($stepId)->delete();

        return back()->with('success', 'Step deleted.');
    }

    public function enroll(int $id): RedirectResponse
    {
        $sequence = EmailSequence::query()->findOrFail($id);
        $count = $this->runner->enroll($sequence);

        return back()->with('success', "Enrolled {$count} contacts.");
    }

    public function updateSequenceStatus(Request $request, int $id): RedirectResponse
    {
        $status = $request->validate(['status' => 'required|in:draft,active,paused'])['status'];
        $this->sequences->update(['status' => $status], $id);

        return back()->with('success', 'Sequence updated.');
    }

    public function updateEnrollmentStatus(Request $request, int $id, int $enrollmentId): RedirectResponse
    {
        $status = $request->validate(['status' => 'required|in:active,paused,unsubscribed'])['status'];
        $enrollment = EmailEnrollment::query()->where('sequence_id', $id)->findOrFail($enrollmentId);
        $enrollment->forceFill([
            'status' => $status,
            'next_run_at' => $status === 'active' ? ($enrollment->next_run_at ?? now()) : null,
        ])->save();

        return back()->with('success', 'Enrollment updated.');
    }

    public function attributions(): View
    {
        return view('marketing_automation::attributions', [
            'rows' => Attribution::query()->with('campaign')->latest('id')->limit(100)->get(),
        ]);
    }

    public function engagement(): View
    {
        return view('marketing_automation::engagement', [
            'rules' => EngagementRule::query()->orderBy('event_type')->get(),
            'events' => EngagementEvent::query()->latest('id')->limit(50)->get(),
            'eventTypes' => Catalog::ENGAGEMENT_EVENTS,
            'total' => (int) EngagementScore::query()->sum('points'),
        ]);
    }

    public function storeEngagementRule(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'event_type' => 'required|in:email_open,email_click,form_submit,unsubscribe',
            'points' => 'required|integer|min:-1000|max:1000',
        ]);

        $this->engagementRules->create([
            ...$data,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'Engagement rule saved.');
    }

    public function destroyEngagementRule(int $id): RedirectResponse
    {
        $this->engagementRules->delete($id);

        return back()->with('success', 'Engagement rule deleted.');
    }

    public function deliverability(): View
    {
        $messages = EmailMessage::query()->latest('id')->limit(100)->get();

        return view('marketing_automation::deliverability', [
            'messages' => $messages,
            'counts' => [
                'sent' => EmailMessage::query()->whereNotNull('sent_at')->count(),
                'opened' => EmailMessage::query()->whereNotNull('opened_at')->count(),
                'clicked' => EmailMessage::query()->whereNotNull('clicked_at')->count(),
                'bounced' => EmailMessage::query()->where('status', 'bounced')->count(),
                'failed' => EmailMessage::query()->where('status', 'failed')->count(),
                'unsubscribed' => EmailMessage::query()->where('status', 'unsubscribed')->count(),
                'suppressed' => EmailMessage::query()->where('status', 'suppressed')->count(),
            ],
        ]);
    }

    public function bounce(int $id): RedirectResponse
    {
        $message = EmailMessage::query()->findOrFail($id);
        $message->forceFill(['status' => 'bounced'])->save();

        return back()->with('success', 'Message marked bounced.');
    }

    /**
     * @return array<string, mixed>
     */
    private function campaignData(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'status' => 'required|in:draft,active,paused,completed',
            'budget' => 'required|numeric|min:0',
            'spent' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:8',
            'audience_id' => 'nullable|integer|exists:ma_audiences,id',
            'source_name' => 'nullable|string|max:255',
            'utm_campaign' => 'nullable|string|max:255',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
        ]);

        $data['spent'] = $data['spent'] ?? 0;
        $data['currency'] = $data['currency'] ?: 'USD';
        $data['audience_id'] = $data['audience_id'] ?: null;
        $data['starts_at'] = $request->filled('starts_at') ? $request->date('starts_at') : null;
        $data['ends_at'] = $request->filled('ends_at') ? $request->date('ends_at') : null;

        return $data;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return list<array{attribute: string, operator: string, value: string}>
     */
    private function conditionsFor(string $entity, array $rows): array
    {
        $allowed = array_keys(Catalog::ATTRIBUTES[$entity] ?? []);
        $conditions = [];

        foreach ($rows as $row) {
            $attribute = (string) ($row['attribute'] ?? '');
            $operator = (string) ($row['operator'] ?? 'eq');

            if (! in_array($attribute, $allowed, true) || ! in_array($operator, Catalog::OPERATORS, true)) {
                continue;
            }

            $conditions[] = [
                'attribute' => $attribute,
                'operator' => $operator,
                'value' => (string) ($row['value'] ?? ''),
            ];
        }

        return $conditions;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return list<array{branch: string, type: string, config: array<string, mixed>}>
     */
    private function actionsFor(string $entity, array $rows): array
    {
        $actions = [];

        foreach ($rows as $row) {
            $type = (string) ($row['type'] ?? '');
            $branch = (string) ($row['branch'] ?? '');

            if (! in_array($type, Catalog::ACTION_TYPES, true) || ! in_array($branch, ['when_match', 'when_else'], true)) {
                continue;
            }

            $field = (string) ($row['field'] ?? '');

            if ($type === 'update' && ! in_array($field, Catalog::UPDATE_FIELDS[$entity] ?? [], true)) {
                continue;
            }

            $actions[] = [
                'branch' => $branch,
                'type' => $type,
                'config' => array_filter([
                    'minutes' => $row['minutes'] ?? null,
                    'title' => $row['title'] ?? null,
                    'subject' => $row['subject'] ?? null,
                    'body' => $row['body'] ?? null,
                    'field' => $field !== '' ? $field : null,
                    'value' => $row['value'] ?? null,
                    'link_url' => $row['link_url'] ?? null,
                    'link_label' => $row['link_label'] ?? null,
                ], fn ($value) => $value !== null && $value !== ''),
            ];
        }

        return $actions;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return list<array{attribute: string, operator: string, value: string}>
     */
    private function audienceRules(array $rows): array
    {
        $rules = [];

        foreach ($rows as $row) {
            $attribute = (string) ($row['attribute'] ?? '');
            $operator = (string) ($row['operator'] ?? 'contains');

            if (! isset(Catalog::AUDIENCE_FIELDS[$attribute]) || ! in_array($operator, Catalog::OPERATORS, true)) {
                continue;
            }

            $rules[] = [
                'attribute' => $attribute,
                'operator' => $operator,
                'value' => (string) ($row['value'] ?? ''),
            ];
        }

        return $rules;
    }
}
