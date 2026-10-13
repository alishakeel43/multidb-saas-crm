<?php

namespace Webkul\MarketingAutomation\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Webkul\MarketingAutomation\Models\Attribution;
use Webkul\MarketingAutomation\Models\Campaign;
use Webkul\MarketingAutomation\Support\CrmEntity;

class AttributionRecorder
{
    public function __construct(private EngagementScorer $engagement) {}

    public function capture(object $entity, ?Request $request = null): void
    {
        if (! Schema::hasTable('ma_attributions')) {
            return;
        }

        $context = CrmEntity::context($entity);

        if ($context === null) {
            return;
        }

        $request ??= request();
        $payload = [
            'utm_source' => $this->clean($request->input('utm_source')),
            'utm_medium' => $this->clean($request->input('utm_medium')),
            'utm_campaign' => $this->clean($request->input('utm_campaign')),
            'utm_content' => $this->clean($request->input('utm_content')),
            'utm_term' => $this->clean($request->input('utm_term')),
            'referrer' => $this->clean($request->input('ma_referrer'), 2000),
            'landing_url' => $this->clean($request->input('ma_landing_url'), 2000),
        ];

        if (! $payload['utm_source'] && ! $payload['utm_medium'] && ! $payload['utm_campaign'] && ! $payload['utm_content'] && ! $payload['utm_term']) {
            return;
        }

        $leadId = $context['lead_id'];
        $personId = $context['person_id'];

        $duplicate = Attribution::query()
            ->when($leadId, fn ($query) => $query->where('lead_id', $leadId), fn ($query) => $query->where('person_id', $personId))
            ->where('utm_source', $payload['utm_source'])
            ->where('utm_campaign', $payload['utm_campaign'])
            ->where('created_at', '>=', now()->subMinutes(2))
            ->exists();

        if ($duplicate) {
            return;
        }

        $campaign = $this->matchCampaign($payload['utm_campaign'], $payload['utm_source']);
        $webFormId = $request->route('id');

        Attribution::query()->create([
            ...$payload,
            'lead_id' => $leadId,
            'person_id' => $personId,
            'campaign_id' => $campaign?->id,
            'web_form_id' => is_numeric($webFormId) ? (int) $webFormId : null,
        ]);

        if ($leadId && $payload['utm_source'] && Schema::hasTable('lead_sources') && Schema::hasTable('leads')) {
            $sourceId = DB::table('lead_sources')->where('name', $payload['utm_source'])->value('id');

            if ($sourceId) {
                DB::table('leads')->where('id', $leadId)->update([
                    'lead_source_id' => $sourceId,
                    'updated_at' => now(),
                ]);
            }
        }

        if ($leadId) {
            $this->engagement->record($leadId, 'form_submit', 'lead:'.$leadId.':form_submit');
        }
    }

    private function matchCampaign(?string $utmCampaign, ?string $utmSource): ?Campaign
    {
        if (! Schema::hasTable('ma_campaigns')) {
            return null;
        }

        $campaigns = Campaign::query()->where('status', 'active')->orderByDesc('id')->get();
        $now = now();

        foreach ($campaigns as $campaign) {
            if ($campaign->starts_at && $campaign->starts_at->gt($now)) {
                continue;
            }

            if ($campaign->ends_at && $campaign->ends_at->lt($now)) {
                continue;
            }

            if ($utmCampaign && strcasecmp((string) $campaign->utm_campaign, $utmCampaign) === 0) {
                return $campaign;
            }

            if (! $utmCampaign && $utmSource && $campaign->source_name && strcasecmp($campaign->source_name, $utmSource) === 0) {
                return $campaign;
            }
        }

        return null;
    }

    private function clean(mixed $value, int $limit = 255): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : mb_substr($value, 0, $limit);
    }
}
