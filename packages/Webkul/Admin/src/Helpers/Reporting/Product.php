<?php

namespace Webkul\Admin\Helpers\Reporting;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Webkul\Lead\Repositories\ProductRepository;

class Product extends AbstractReporting
{
    /**
     * Create a helper instance.
     *
     * @return void
     */
    public function __construct(
        protected ProductRepository $productRepository
    ) {
        parent::__construct();
    }

    /**
     * Gets top-selling products by revenue.
     *
     * @param  int  $limit
     */
    public function getTopSellingProductsByRevenue($limit = null, ?int $pipelineId = null): Collection
    {
        $tablePrefix = DB::getTablePrefix();

        $query = $this->productRepository
            ->resetModel()
            ->join('leads', 'lead_products.lead_id', '=', 'leads.id')
            ->join('lead_pipeline_stages', function ($join) {
                $join->on('leads.lead_pipeline_stage_id', '=', 'lead_pipeline_stages.id')
                    ->where('lead_pipeline_stages.code', 'won');
            })
            ->leftJoin('products', 'lead_products.product_id', '=', 'products.id')
            ->select('lead_products.product_id')
            ->selectRaw('MAX('.$tablePrefix.'products.name) as name')
            ->selectRaw('MAX('.$tablePrefix.'products.price) as catalog_price')
            ->selectRaw('SUM('.$tablePrefix.'lead_products.amount) as revenue')
            ->whereBetween('leads.closed_at', [$this->startDate, $this->endDate])
            ->groupBy('lead_products.product_id')
            ->having(DB::raw('SUM('.$tablePrefix.'lead_products.amount)'), '>', 0)
            ->orderBy('revenue', 'DESC')
            ->limit($limit);

        if ($pipelineId) {
            $query->where('leads.lead_pipeline_id', $pipelineId);
        }

        return $query->get()->map(function ($item) {
            return [
                'id' => $item->product_id,
                'name' => $item->name,
                'price' => $item->catalog_price,
                'formatted_price' => core()->formatBasePrice($item->catalog_price),
                'revenue' => (float) $item->revenue,
                'formatted_revenue' => core()->formatBasePrice($item->revenue),
            ];
        });
    }

    /**
     * Gets top-selling products by quantity.
     *
     * @param  int  $limit
     */
    public function getTopSellingProductsByQuantity($limit = null): Collection
    {
        $tablePrefix = DB::getTablePrefix();

        $items = $this->productRepository
            ->resetModel()
            ->with('product')
            ->leftJoin('leads', 'lead_products.lead_id', '=', 'leads.id')
            ->leftJoin('products', 'lead_products.product_id', '=', 'products.id')
            ->select('*')
            ->addSelect(DB::raw('SUM('.$tablePrefix.'lead_products.quantity) as total_qty_ordered'))
            ->whereBetween('leads.closed_at', [$this->startDate, $this->endDate])
            ->having(DB::raw('SUM('.$tablePrefix.'lead_products.quantity)'), '>', 0)
            ->groupBy('product_id')
            ->orderBy('total_qty_ordered', 'DESC')
            ->limit($limit)
            ->get();

        $items = $items->map(function ($item) {
            return [
                'id' => $item->product_id,
                'name' => $item->name,
                'price' => $item->product?->price,
                'formatted_price' => core()->formatBasePrice($item->price),
                'total_qty_ordered' => $item->total_qty_ordered,
            ];
        });

        return $items;
    }
}
