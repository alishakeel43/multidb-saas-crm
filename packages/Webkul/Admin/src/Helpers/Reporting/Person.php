<?php

namespace Webkul\Admin\Helpers\Reporting;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Webkul\Contact\Repositories\PersonRepository;

class Person extends AbstractReporting
{
    /**
     * Create a helper instance.
     *
     * @return void
     */
    public function __construct(protected PersonRepository $personRepository)
    {
        parent::__construct();
    }

    /**
     * Retrieves total persons and their progress.
     */
    public function getTotalPersonsProgress(): array
    {
        return [
            'previous' => $previous = $this->getTotalPersons($this->lastStartDate, $this->lastEndDate),
            'current' => $current = $this->getTotalPersons($this->startDate, $this->endDate),
            'progress' => $this->getPercentageChange($previous, $current),
        ];
    }

    /**
     * Retrieves total persons by date
     *
     * @param  Carbon  $startDate
     * @param  Carbon  $endDate
     */
    public function getTotalPersons($startDate, $endDate): int
    {
        return $this->personRepository
            ->resetModel()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
    }

    /**
     * Gets top customers by revenue.
     *
     * @param  int  $limit
     */
    public function getTopCustomersByRevenue($limit = null, ?int $pipelineId = null): Collection
    {
        $tablePrefix = DB::getTablePrefix();

        $query = $this->personRepository
            ->resetModel()
            ->join('leads', 'persons.id', '=', 'leads.person_id')
            ->join('lead_pipeline_stages', function ($join) {
                $join->on('leads.lead_pipeline_stage_id', '=', 'lead_pipeline_stages.id')
                    ->where('lead_pipeline_stages.code', 'won');
            })
            ->select('persons.id')
            ->selectRaw('MAX('.$tablePrefix.'persons.name) as name')
            ->selectRaw('MAX('.$tablePrefix.'persons.emails) as emails')
            ->selectRaw('MAX('.$tablePrefix.'persons.contact_numbers) as contact_numbers')
            ->selectRaw('SUM('.$tablePrefix.'leads.lead_value) as revenue')
            ->whereBetween('leads.closed_at', [$this->startDate, $this->endDate])
            ->groupBy('persons.id')
            ->having(DB::raw('SUM('.$tablePrefix.'leads.lead_value)'), '>', 0)
            ->orderBy('revenue', 'DESC')
            ->limit($limit);

        if ($pipelineId) {
            $query->where('leads.lead_pipeline_id', $pipelineId);
        }

        $items = $query->get();

        $items = $items->map(function ($item) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'emails' => $item->emails,
                'contact_numbers' => $item->contact_numbers,
                'revenue' => (float) $item->revenue,
                'formatted_revenue' => core()->formatBasePrice($item->revenue),
            ];
        });

        return $items;
    }
}
