<?php

namespace Webkul\MarketingAutomation\Services;

use Illuminate\Support\Facades\Schema;
use Webkul\MarketingAutomation\Models\Campaign;

class CampaignExtension
{
    public function sync(object $marketingCampaign): void
    {
        if (! Schema::hasTable('ma_campaigns') || ! Schema::hasColumn('ma_campaigns', 'marketing_campaign_id')) {
            return;
        }

        if (! request()->exists('ma_budget') && ! request()->exists('ma_utm_campaign') && ! request()->exists('ma_source_name')) {
            return;
        }

        $id = (int) ($marketingCampaign->id ?? 0);

        if ($id < 1) {
            return;
        }

        Campaign::query()->updateOrCreate(
            ['marketing_campaign_id' => $id],
            [
                'name' => (string) ($marketingCampaign->name ?? 'Campaign'),
                'status' => (int) ($marketingCampaign->status ?? 0) === 1 ? 'active' : 'paused',
                'budget' => request()->input('ma_budget', 0),
                'spent' => request()->input('ma_spent', 0),
                'currency' => request()->input('ma_currency') ?: 'USD',
                'audience_id' => request()->filled('ma_audience_id') ? (int) request()->input('ma_audience_id') : null,
                'source_name' => request()->input('ma_source_name') ?: null,
                'utm_campaign' => request()->input('ma_utm_campaign') ?: null,
                'starts_at' => request()->filled('ma_starts_at') ? request()->date('ma_starts_at') : null,
                'ends_at' => request()->filled('ma_ends_at') ? request()->date('ma_ends_at') : null,
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function mergeIntoPayload(array $payload): array
    {
        $id = (int) data_get($payload, 'data.id', 0);

        if ($id < 1 || ! Schema::hasTable('ma_campaigns') || ! Schema::hasColumn('ma_campaigns', 'marketing_campaign_id')) {
            return $payload;
        }

        $campaign = Campaign::query()->where('marketing_campaign_id', $id)->first();

        if (! $campaign) {
            return $payload;
        }

        $report = app(CampaignReport::class)->summarize($campaign);

        $payload['data']['ma_budget'] = $campaign->budget;
        $payload['data']['ma_spent'] = $campaign->spent;
        $payload['data']['ma_currency'] = $campaign->currency;
        $payload['data']['ma_audience_id'] = $campaign->audience_id;
        $payload['data']['ma_source_name'] = $campaign->source_name;
        $payload['data']['ma_utm_campaign'] = $campaign->utm_campaign;
        $payload['data']['ma_starts_at'] = optional($campaign->starts_at)->format('Y-m-d\TH:i');
        $payload['data']['ma_ends_at'] = optional($campaign->ends_at)->format('Y-m-d\TH:i');
        $payload['data']['ma_results'] = 'Leads '.$report['leads'].' · Remaining '.$report['remaining'];

        return $payload;
    }

    public function forget(mixed $marketingCampaign): void
    {
        if (! Schema::hasTable('ma_campaigns') || ! Schema::hasColumn('ma_campaigns', 'marketing_campaign_id')) {
            return;
        }

        $id = is_object($marketingCampaign) ? (int) ($marketingCampaign->id ?? 0) : (int) $marketingCampaign;

        if ($id > 0) {
            Campaign::query()->where('marketing_campaign_id', $id)->delete();
        }
    }
}
