<?php

namespace Webkul\Admin\Helpers;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Webkul\Admin\Helpers\Reporting\Activity;
use Webkul\Admin\Helpers\Reporting\Lead;
use Webkul\Admin\Helpers\Reporting\Organization;
use Webkul\Admin\Helpers\Reporting\Person;
use Webkul\Admin\Helpers\Reporting\Product;
use Webkul\Admin\Helpers\Reporting\Quote;

class Dashboard
{
    /**
     * Create a controller instance.
     *
     * @return void
     */
    public function __construct(
        protected Lead $leadReporting,
        protected Activity $activityReporting,
        protected Product $productReporting,
        protected Person $personReporting,
        protected Organization $organizationReporting,
        protected Quote $quoteReporting,
    ) {}

    /**
     * Re-read dates (and the lead pipeline) from the current request.
     */
    protected function applyRequest(): void
    {
        $this->leadReporting->applyRequest();
        $this->activityReporting->applyRequest();
        $this->productReporting->applyRequest();
        $this->personReporting->applyRequest();
        $this->organizationReporting->applyRequest();
        $this->quoteReporting->applyRequest();
    }

    /**
     * Returns the overall revenue statistics.
     */
    public function getRevenueStats(): array
    {
        $this->applyRequest();

        return [
            'total_won_revenue' => $this->leadReporting->getTotalWonLeadValueProgress(),
            'total_lost_revenue' => $this->leadReporting->getTotalLostLeadValueProgress(),
        ];
    }

    /**
     * Returns the overall statistics.
     */
    public function getOverAllStats(): array
    {
        $this->applyRequest();

        return [
            'total_leads' => $this->leadReporting->getTotalLeadsProgress(),
            'average_lead_value' => $this->leadReporting->getAverageLeadValueProgress(),
            'average_leads_per_day' => $this->leadReporting->getAverageLeadsPerDayProgress(),
            'total_quotations' => $this->quoteReporting->getTotalQuotesProgress(),
            'total_persons' => $this->personReporting->getTotalPersonsProgress(),
            'total_organizations' => $this->organizationReporting->getTotalOrganizationsProgress(),
        ];
    }

    /**
     * Returns leads statistics.
     */
    public function getTotalLeadsStats(): array
    {
        $this->applyRequest();

        return [
            'all' => [
                'over_time' => $this->leadReporting->getTotalLeadsOverTime(),
            ],

            'won' => [
                'over_time' => $this->leadReporting->getTotalWonLeadsOverTime(),
            ],
            'lost' => [
                'over_time' => $this->leadReporting->getTotalLostLeadsOverTime(),
            ],
        ];
    }

    /**
     * Returns leads revenue statistics by sources.
     */
    public function getLeadsStatsBySources(): mixed
    {
        $this->applyRequest();

        return $this->leadReporting->getTotalWonLeadValueBySources();
    }

    /**
     * Returns leads revenue statistics by types.
     */
    public function getLeadsStatsByTypes(): mixed
    {
        $this->applyRequest();

        return $this->leadReporting->getTotalWonLeadValueByTypes();
    }

    /**
     * Returns open leads statistics by states.
     */
    public function getOpenLeadsByStates(): mixed
    {
        $this->applyRequest();

        return $this->leadReporting->getOpenLeadsByStates();
    }

    /**
     * Returns top selling products statistics.
     */
    public function getTopSellingProducts(): Collection
    {
        $this->applyRequest();

        $pipelineId = $this->leadReporting->resolvedPipelineId();

        if (! $pipelineId) {
            return collect();
        }

        return $this->productReporting->getTopSellingProductsByRevenue(5, $pipelineId);
    }

    /**
     * Returns top persons statistics.
     */
    public function getTopPersons(): Collection
    {
        $this->applyRequest();

        $pipelineId = $this->leadReporting->resolvedPipelineId();

        if (! $pipelineId) {
            return collect();
        }

        return $this->personReporting->getTopCustomersByRevenue(5, $pipelineId);
    }

    /**
     * Get the start date.
     *
     * @return \Carbon\Carbon
     */
    public function getStartDate(): Carbon
    {
        $this->applyRequest();

        return $this->leadReporting->getStartDate();
    }

    /**
     * Get the end date.
     *
     * @return \Carbon\Carbon
     */
    public function getEndDate(): Carbon
    {
        $this->applyRequest();

        return $this->leadReporting->getEndDate();
    }

    /**
     * Returns date range. The year is included only when the range spans more
     * than one calendar year, so longer ranges stay unambiguous.
     */
    public function getDateRange(): string
    {
        $startDate = $this->getStartDate();

        $endDate = $this->getEndDate();

        $format = $startDate->year === $endDate->year ? 'd M' : 'd M Y';

        return $startDate->format($format).' - '.$endDate->format($format);
    }
}
