<?php

namespace Webkul\MarketingAutomation\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\MarketingAutomation\Models\EmailEnrollment;
use Webkul\MarketingAutomation\Models\EmailMessage;
use Webkul\MarketingAutomation\Models\EmailSequence;
use Webkul\MarketingAutomation\Models\EmailStep;
use Webkul\MarketingAutomation\Repositories\AudienceRepository;
use Webkul\MarketingAutomation\Repositories\EmailSequenceRepository;
use Webkul\MarketingAutomation\Repositories\EngagementRuleRepository;
use Webkul\MarketingAutomation\Services\SequenceRunner;
use Webkul\MarketingAutomation\Support\Catalog;

class MarketingAutomationController extends Controller
{
    public function __construct(
        private AudienceRepository $audiences,
        private EmailSequenceRepository $sequences,
        private EngagementRuleRepository $engagementRules,
        private SequenceRunner $runner,
    ) {}

    public function index(): RedirectResponse
    {
        return redirect()->route('admin.settings.marketing.campaigns.index');
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

    public function storeSequence(Request $request): RedirectResponse
    {
        $request->merge([
            'campaign_id' => $request->input('campaign_id') ?: null,
            'audience_id' => $request->input('audience_id') ?: null,
        ]);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'status' => 'required|in:draft,active,paused',
            'campaign_id' => 'nullable|integer|exists:ma_campaigns,id',
            'audience_id' => 'nullable|integer|exists:ma_audiences,id',
        ]);

        $this->sequences->create($data);

        return back()->with('success', 'Sequence saved. Add steps, then enroll the audience.');
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

    public function bounce(int $id): RedirectResponse
    {
        $message = EmailMessage::query()->findOrFail($id);
        $message->forceFill(['status' => 'bounced'])->save();

        return back()->with('success', 'Message marked bounced.');
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
