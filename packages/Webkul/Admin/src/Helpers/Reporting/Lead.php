<?php

namespace Webkul\Admin\Helpers\Reporting;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Webkul\Lead\Repositories\LeadRepository;
use Webkul\Lead\Repositories\PipelineRepository;
use Webkul\Lead\Repositories\StageRepository;

class Lead extends AbstractReporting
{
    /**
     * The channel ids.
     */
    protected array $stageIds = [];

    /**
     * The all stage ids.
     */
    protected array $allStageIds = [];

    /**
     * The won stage ids.
     */
    protected array $wonStageIds = [];

    /**
     * The lost stage ids.
     */
    protected array $lostStageIds = [];

    /**
     * The pipeline the dashboard is scoped to.
     */
    protected $pipeline;

    /**
     * Whether pipeline/stage ids have been resolved for this request.
     */
    protected bool $pipelineResolved = false;

    /**
     * Won and lost revenue for this request, loaded once for both cards.
     *
     * @var array{current_won: float, current_lost: float, previous_won: float, previous_lost: float}|null
     */
    protected ?array $closedRevenueCache = null;

    /**
     * Create a helper instance.
     *
     * @return void
     */
    public function __construct(
        protected LeadRepository $leadRepository,
        protected StageRepository $stageRepository,
        protected PipelineRepository $pipelineRepository
    ) {
        // Pipeline/stages are resolved lazily on first use. Laravel may
        // instantiate this helper while gathering controller middleware —
        // before session auth + tenant context exist — which would freeze a
        // null pipeline and zero-out dashboard lead/revenue widgets.
        parent::__construct();
    }

    /**
     * Dates and the selected pipeline both come from the current request.
     * A reused controller must not keep the previous request's pipeline.
     */
    public function applyRequest(): void
    {
        parent::applyRequest();

        $this->pipelineResolved = false;
        $this->closedRevenueCache = null;
        $this->pipeline = null;
    }

    /**
     * Ensure pipeline + stage id sets are loaded under the current tenant.
     */
    protected function ensurePipelineResolved(): void
    {
        if ($this->pipelineResolved) {
            return;
        }

        $this->pipelineResolved = true;

        $this->pipeline = request('pipeline_id')
            ? $this->pipelineRepository->find(request('pipeline_id'))
            : null;

        if (! $this->pipeline) {
            $this->pipeline = $this->pipelineRepository->getDefaultPipeline();
        }

        /**
         * Multi-tenant workspaces may briefly have no pipeline (new tenant /
         * missing seed). Keep dashboard bootable with empty stage sets.
         */
        if (! $this->pipeline) {
            $this->allStageIds = [];
            $this->wonStageIds = [];
            $this->lostStageIds = [];

            return;
        }

        $stages = $this->pipeline->stages;

        $this->allStageIds = $stages->pluck('id')->toArray();

        $this->wonStageIds = $stages->where('code', 'won')->pluck('id')->toArray();

        $this->lostStageIds = $stages->where('code', 'lost')->pluck('id')->toArray();
    }

    /**
     * Returns current customers over time
     *
     * @param  string  $period
     */
    public function getTotalLeadsOverTime($period = 'auto'): array
    {
        $this->ensurePipelineResolved();

        $this->stageIds = $this->allStageIds;

        $period = $this->determinePeriod($period);

        return $this->getOverTimeStats($this->startDate, $this->endDate, 'lead_value', 'created_at', $period);
    }

    /**
     * Returns current customers over time
     *
     * @param  string  $period
     */
    public function getTotalWonLeadsOverTime($period = 'auto'): array
    {
        $this->ensurePipelineResolved();

        $this->stageIds = $this->wonStageIds;

        $period = $this->determinePeriod($period);

        return $this->getOverTimeStats($this->startDate, $this->endDate, 'lead_value', 'closed_at', $period);
    }

    /**
     * Returns current customers over time
     *
     * @param  string  $period
     */
    public function getTotalLostLeadsOverTime($period = 'auto'): array
    {
        $this->ensurePipelineResolved();

        $this->stageIds = $this->lostStageIds;

        $period = $this->determinePeriod($period);

        return $this->getOverTimeStats($this->startDate, $this->endDate, 'lead_value', 'closed_at', $period);
    }

    /**
     * Determine the appropriate period based on date range
     *
     * @param  string  $period
     */
    protected function determinePeriod($period = 'auto'): string
    {
        if ($period !== 'auto') {
            return $period;
        }

        $span = $this->inclusiveDayCount($this->startDate, $this->endDate) - 1;

        if ($span > 365 * 3) {
            return 'year';
        } elseif ($span > 183) {
            return 'month';
        } elseif ($span > 60) {
            return 'week';
        }

        return 'day';
    }

    /**
     * Retrieves total leads and their progress.
     */
    public function getTotalLeadsProgress(): array
    {
        return [
            'previous' => $previous = $this->getTotalLeads($this->lastStartDate, $this->lastEndDate),
            'current' => $current = $this->getTotalLeads($this->startDate, $this->endDate),
            'progress' => $this->getPercentageChange($previous, $current),
        ];
    }

    /**
     * Retrieves total leads by date
     *
     * @param  Carbon  $startDate
     * @param  Carbon  $endDate
     */
    public function getTotalLeads($startDate, $endDate): int
    {
        $this->ensurePipelineResolved();

        if (! $this->pipeline) {
            return 0;
        }

        return $this->leadRepository
            ->resetModel()
            ->where('lead_pipeline_id', $this->pipeline->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
    }

    /**
     * Retrieves average leads per day and their progress.
     */
    public function getAverageLeadsPerDayProgress(): array
    {
        return [
            'previous' => $previous = $this->getAverageLeadsPerDay($this->lastStartDate, $this->lastEndDate),
            'current' => $current = $this->getAverageLeadsPerDay($this->startDate, $this->endDate),
            'progress' => $this->getPercentageChange($previous, $current),
        ];
    }

    /**
     * Retrieves average leads per day
     *
     * @param  Carbon  $startDate
     * @param  Carbon  $endDate
     */
    public function getAverageLeadsPerDay($startDate, $endDate): float
    {
        return $this->getTotalLeads($startDate, $endDate) / $this->inclusiveDayCount($startDate, $endDate);
    }

    /**
     * Retrieves total lead value and their progress.
     */
    public function getTotalLeadValueProgress(): array
    {
        return [
            'previous' => $previous = $this->getTotalLeadValue($this->lastStartDate, $this->lastEndDate),
            'current' => $current = $this->getTotalLeadValue($this->startDate, $this->endDate),
            'formatted_total' => core()->formatBasePrice($current),
            'progress' => $this->getPercentageChange($previous, $current),
        ];
    }

    /**
     * Retrieves total lead value
     *
     * @param  Carbon  $startDate
     * @param  Carbon  $endDate
     */
    public function getTotalLeadValue($startDate, $endDate): float
    {
        $this->ensurePipelineResolved();

        if (! $this->pipeline) {
            return 0;
        }

        return (float) $this->leadRepository
            ->resetModel()
            ->where('lead_pipeline_id', $this->pipeline->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('lead_value');
    }

    /**
     * Retrieves average lead value and their progress.
     */
    public function getAverageLeadValueProgress(): array
    {
        return [
            'previous' => $previous = $this->getAverageLeadValue($this->lastStartDate, $this->lastEndDate),
            'current' => $current = $this->getAverageLeadValue($this->startDate, $this->endDate),
            'formatted_total' => core()->formatBasePrice($current),
            'progress' => $this->getPercentageChange($previous, $current),
        ];
    }

    /**
     * Retrieves average lead value
     *
     * @param  Carbon  $startDate
     * @param  Carbon  $endDate
     */
    public function getAverageLeadValue($startDate, $endDate): float
    {
        $this->ensurePipelineResolved();

        if (! $this->pipeline) {
            return 0;
        }

        return (float) ($this->leadRepository
            ->resetModel()
            ->where('lead_pipeline_id', $this->pipeline->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->avg('lead_value') ?? 0);
    }

    /**
     * Retrieves total won lead value and their progress.
     */
    public function getTotalWonLeadValueProgress(): array
    {
        $closed = $this->closedRevenue();
        $current = $closed['current_won'];

        return [
            'previous' => $closed['previous_won'],
            'current' => $current,
            'formatted_total' => core()->formatBasePrice($current),
            'progress' => $this->sharePercentage($current, $current + $closed['current_lost']),
        ];
    }

    /**
     * Retrieves average won lead value
     *
     * @param  Carbon  $startDate
     * @param  Carbon  $endDate
     * @return array
     */
    public function getTotalWonLeadValue($startDate, $endDate): float
    {
        $this->ensurePipelineResolved();

        if (! $this->pipeline || $this->wonStageIds === []) {
            return 0.0;
        }

        return (float) $this->leadRepository
            ->resetModel()
            ->where('lead_pipeline_id', $this->pipeline->id)
            ->whereIn('lead_pipeline_stage_id', $this->wonStageIds)
            ->whereBetween('closed_at', [$startDate, $endDate])
            ->sum('lead_value');
    }

    /**
     * Retrieves average lost lead value and their progress.
     */
    public function getTotalLostLeadValueProgress(): array
    {
        $closed = $this->closedRevenue();
        $current = $closed['current_lost'];

        return [
            'previous' => $closed['previous_lost'],
            'current' => $current,
            'formatted_total' => core()->formatBasePrice($current),
            'progress' => $this->sharePercentage($current, $closed['current_won'] + $current),
        ];
    }

    /**
     * Retrieves average lost lead value
     *
     * @param  Carbon  $startDate
     * @param  Carbon  $endDate
     * @return array
     */
    public function getTotalLostLeadValue($startDate, $endDate): float
    {
        $this->ensurePipelineResolved();

        if (! $this->pipeline || $this->lostStageIds === []) {
            return 0.0;
        }

        return (float) $this->leadRepository
            ->resetModel()
            ->where('lead_pipeline_id', $this->pipeline->id)
            ->whereIn('lead_pipeline_stage_id', $this->lostStageIds)
            ->whereBetween('closed_at', [$startDate, $endDate])
            ->sum('lead_value');
    }

    /**
     * Won and lost revenue for the selected range and the preceding range.
     *
     * Closed deals are dated by closed_at. One query supplies both cards.
     *
     * @return array{current_won: float, current_lost: float, previous_won: float, previous_lost: float}
     */
    protected function closedRevenue(): array
    {
        if (isset($this->closedRevenueCache)) {
            return $this->closedRevenueCache;
        }

        $empty = [
            'current_won' => 0.0,
            'current_lost' => 0.0,
            'previous_won' => 0.0,
            'previous_lost' => 0.0,
        ];

        $this->ensurePipelineResolved();

        if (! $this->pipeline || ($this->wonStageIds === [] && $this->lostStageIds === [])) {
            return $this->closedRevenueCache = $empty;
        }

        $wonIn = $this->wonStageIds === [] ? 'NULL' : implode(',', array_map('intval', $this->wonStageIds));
        $lostIn = $this->lostStageIds === [] ? 'NULL' : implode(',', array_map('intval', $this->lostStageIds));
        $lead = DB::getTablePrefix().'leads';

        $row = $this->leadRepository
            ->resetModel()
            ->where('lead_pipeline_id', $this->pipeline->id)
            ->whereNotNull('closed_at')
            ->where(function ($query) {
                $query->whereBetween('closed_at', [$this->startDate, $this->endDate])
                    ->orWhereBetween('closed_at', [$this->lastStartDate, $this->lastEndDate]);
            })
            ->selectRaw(
                "COALESCE(SUM(CASE WHEN {$lead}.lead_pipeline_stage_id IN ($wonIn) AND {$lead}.closed_at BETWEEN ? AND ? THEN {$lead}.lead_value ELSE 0 END), 0) as current_won,
                COALESCE(SUM(CASE WHEN {$lead}.lead_pipeline_stage_id IN ($lostIn) AND {$lead}.closed_at BETWEEN ? AND ? THEN {$lead}.lead_value ELSE 0 END), 0) as current_lost,
                COALESCE(SUM(CASE WHEN {$lead}.lead_pipeline_stage_id IN ($wonIn) AND {$lead}.closed_at BETWEEN ? AND ? THEN {$lead}.lead_value ELSE 0 END), 0) as previous_won,
                COALESCE(SUM(CASE WHEN {$lead}.lead_pipeline_stage_id IN ($lostIn) AND {$lead}.closed_at BETWEEN ? AND ? THEN {$lead}.lead_value ELSE 0 END), 0) as previous_lost",
                [
                    $this->startDate, $this->endDate,
                    $this->startDate, $this->endDate,
                    $this->lastStartDate, $this->lastEndDate,
                    $this->lastStartDate, $this->lastEndDate,
                ]
            )
            ->first();

        return $this->closedRevenueCache = [
            'current_won' => (float) ($row->current_won ?? 0),
            'current_lost' => (float) ($row->current_lost ?? 0),
            'previous_won' => (float) ($row->previous_won ?? 0),
            'previous_lost' => (float) ($row->previous_lost ?? 0),
        ];
    }

    /**
     * Retrieves total lead value by sources.
     */
    public function getTotalWonLeadValueBySources()
    {
        $this->ensurePipelineResolved();

        if (! $this->pipeline || $this->wonStageIds === []) {
            return collect();
        }

        $prefix = DB::getTablePrefix();

        return $this->leadRepository
            ->resetModel()
            ->select(
                DB::raw("COALESCE(MAX({$prefix}lead_sources.name), 'Unassigned') as name"),
                DB::raw("COALESCE(SUM({$prefix}leads.lead_value), 0) as total")
            )
            ->leftJoin('lead_sources', 'leads.lead_source_id', '=', 'lead_sources.id')
            ->where('leads.lead_pipeline_id', $this->pipeline->id)
            ->whereIn('leads.lead_pipeline_stage_id', $this->wonStageIds)
            ->whereBetween('leads.closed_at', [$this->startDate, $this->endDate])
            ->groupBy('leads.lead_source_id')
            ->having('total', '>', 0)
            ->get()
            ->each(fn ($row) => $row->total = (float) $row->total);
    }

    /**
     * Retrieves total lead value by types.
     */
    public function getTotalWonLeadValueByTypes()
    {
        $this->ensurePipelineResolved();

        if (! $this->pipeline || $this->wonStageIds === []) {
            return collect();
        }

        $prefix = DB::getTablePrefix();

        return $this->leadRepository
            ->resetModel()
            ->select(
                DB::raw("COALESCE(MAX({$prefix}lead_types.name), 'Unassigned') as name"),
                DB::raw("COALESCE(SUM({$prefix}leads.lead_value), 0) as total")
            )
            ->leftJoin('lead_types', 'leads.lead_type_id', '=', 'lead_types.id')
            ->where('leads.lead_pipeline_id', $this->pipeline->id)
            ->whereIn('leads.lead_pipeline_stage_id', $this->wonStageIds)
            ->whereBetween('leads.closed_at', [$this->startDate, $this->endDate])
            ->groupBy('leads.lead_type_id')
            ->having('total', '>', 0)
            ->get()
            ->each(fn ($row) => $row->total = (float) $row->total);
    }

    /**
     * Retrieves open leads by states.
     */
    public function getOpenLeadsByStates()
    {
        $this->ensurePipelineResolved();

        if (! $this->pipeline) {
            return collect();
        }

        $prefix = DB::getTablePrefix();

        $query = $this->leadRepository
            ->resetModel()
            ->select(
                'lead_pipeline_stages.name',
                DB::raw("COUNT({$prefix}leads.id) as total")
            )
            ->leftJoin('lead_pipeline_stages', 'leads.lead_pipeline_stage_id', '=', 'lead_pipeline_stages.id')
            ->where('leads.lead_pipeline_id', $this->pipeline->id)
            ->whereBetween('leads.created_at', [$this->startDate, $this->endDate]);

        if ($this->wonStageIds !== []) {
            $query->whereNotIn('leads.lead_pipeline_stage_id', $this->wonStageIds);
        }

        if ($this->lostStageIds !== []) {
            $query->whereNotIn('leads.lead_pipeline_stage_id', $this->lostStageIds);
        }

        return $query
            ->groupBy('leads.lead_pipeline_stage_id', 'lead_pipeline_stages.name')
            ->orderByDesc('total')
            ->get()
            ->each(fn ($row) => $row->total = (int) $row->total);
    }

    /**
     * Returns over time stats.
     *
     * @param  Carbon  $startDate
     * @param  Carbon  $endDate
     * @param  string  $valueColumn
     * @param  string  $dateColumn
     * @param  string  $period
     */
    public function getOverTimeStats($startDate, $endDate, $valueColumn, $dateColumn = 'created_at', $period = 'auto'): array
    {
        $this->ensurePipelineResolved();
        $period = $this->determinePeriod($period);

        $intervals = $this->generateTimeIntervals($startDate, $endDate, $period);

        $groupColumn = $this->getGroupColumn($dateColumn, $period);

        $results = collect();

        if ($this->pipeline && $this->stageIds !== []) {
            $prefix = DB::getTablePrefix();

            $results = $this->leadRepository
                ->resetModel()
                ->select(
                    DB::raw("$groupColumn AS date"),
                    DB::raw("COUNT(DISTINCT {$prefix}leads.id) AS count"),
                    DB::raw("COALESCE(SUM({$prefix}leads.lead_value), 0) AS total")
                )
                ->where('lead_pipeline_id', $this->pipeline->id)
                ->whereIn('lead_pipeline_stage_id', $this->stageIds)
                ->whereBetween($dateColumn, [$startDate, $endDate])
                ->groupBy(DB::raw($groupColumn))
                ->orderBy(DB::raw($groupColumn))
                ->get();
        }
        $resultLookup = $results->keyBy(
            fn ($row) => $this->normalizeGroupKey($row->date, $period)
        );

        $stats = [];

        foreach ($intervals as $interval) {
            $result = $resultLookup->get($interval['key']);

            $stats[] = [
                'label' => $interval['label'],
                'count' => $result ? (int) $result->count : 0,
                'total' => $result ? (float) $result->total : 0,
            ];
        }

        return $stats;
    }

    /**
     * Generate time intervals based on period
     */
    protected function generateTimeIntervals(Carbon $startDate, Carbon $endDate, string $period): array
    {
        $intervals = [];
        $seen = [];
        $current = $startDate->copy();

        while ($current <= $endDate) {
            $key = $this->formatDateForGrouping($current, $period);

            if (! isset($seen[$key])) {
                $seen[$key] = true;

                $intervals[] = [
                    'key' => $key,
                    'label' => $this->formatDateForLabel($current, $period),
                ];
            }

            switch ($period) {
                case 'day':
                    $current->addDay();

                    break;
                case 'week':
                    $current->addWeek();

                    break;
                case 'month':
                    $current->addMonth();

                    break;
                case 'year':
                    $current->addYear();

                    break;
            }
        }

        return $intervals;
    }

    /**
     * The pipeline used by every lead widget, or null when the workspace has none.
     */
    public function resolvedPipelineId(): ?int
    {
        $this->ensurePipelineResolved();

        return $this->pipeline?->id ? (int) $this->pipeline->id : null;
    }

    /**
     * Match SQL date buckets to the labels generated in PHP.
     */
    protected function normalizeGroupKey(mixed $value, string $period): string
    {
        $value = (string) $value;

        return match ($period) {
            'day' => substr($value, 0, 10),
            'year' => substr($value, 0, 4),
            default => $value,
        };
    }

    /**
     * Get the SQL group column based on period
     */
    protected function getGroupColumn(string $dateColumn, string $period): string
    {
        switch ($period) {
            case 'day':
                return "DATE($dateColumn)";
            case 'week':
                return "DATE_FORMAT($dateColumn, '%x-%v')";
            case 'month':
                return "DATE_FORMAT($dateColumn, '%Y-%m')";
            case 'year':
                return "YEAR($dateColumn)";
            default:
                return "DATE($dateColumn)";
        }
    }

    /**
     * Format date for grouping key
     */
    protected function formatDateForGrouping(Carbon $date, string $period): string
    {
        switch ($period) {
            case 'day':
                return $date->format('Y-m-d');
            case 'week':
                return $date->format('o-W');
            case 'month':
                return $date->format('Y-m');
            case 'year':
                return $date->format('Y');
            default:
                return $date->format('Y-m-d');
        }
    }

    /**
     * Format date for display label
     */
    protected function formatDateForLabel(Carbon $date, string $period): string
    {
        switch ($period) {
            case 'day':
                return $date->format('M d');
            case 'week':
                return 'Week '.$date->format('W, o');
            case 'month':
                return $date->format('M Y');
            case 'year':
                return $date->format('Y');
            default:
                return $date->format('M d');
        }
    }
}
