<?php

namespace Webkul\QuoteApproval\Services;

use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use Webkul\Quote\Contracts\Quote;
use Webkul\QuoteApproval\Models\QuoteApproval;
use Webkul\QuoteApproval\Repositories\QuoteApprovalRepository;

class QuoteApprovalService
{
    public function __construct(
        protected QuoteApprovalRepository $quoteApprovalRepository,
    ) {}

    public function forQuote(Quote $quote): QuoteApproval
    {
        $existing = $this->quoteApprovalRepository->findOneByField('quote_id', $quote->id);

        if ($existing) {
            return $existing;
        }

        return $this->quoteApprovalRepository->create([
            'quote_id' => $quote->id,
            'status' => QuoteApproval::STATUS_DRAFT,
        ]);
    }

    public function submit(Quote $quote, ?string $notes = null): QuoteApproval
    {
        $approval = $this->forQuote($quote);

        if (in_array($approval->status, [QuoteApproval::STATUS_APPROVED, QuoteApproval::STATUS_PENDING], true)) {
            throw new InvalidArgumentException('Quote is already pending or approved.');
        }

        return $this->quoteApprovalRepository->update([
            'status' => QuoteApproval::STATUS_PENDING,
            'requested_by' => Auth::guard('user')->id(),
            'requested_at' => now(),
            'notes' => $notes,
            'reviewed_by' => null,
            'reviewed_at' => null,
        ], $approval->id);
    }

    public function approve(Quote $quote, ?string $notes = null): QuoteApproval
    {
        return $this->review($quote, QuoteApproval::STATUS_APPROVED, $notes);
    }

    public function reject(Quote $quote, ?string $notes = null): QuoteApproval
    {
        return $this->review($quote, QuoteApproval::STATUS_REJECTED, $notes);
    }

    public function isApproved(Quote $quote): bool
    {
        return $this->forQuote($quote)->status === QuoteApproval::STATUS_APPROVED;
    }

    public function markDelivered(Quote $quote): QuoteApproval
    {
        $approval = $this->forQuote($quote);

        if ($approval->status !== QuoteApproval::STATUS_APPROVED) {
            throw new InvalidArgumentException('Only approved quotes can be marked delivered.');
        }

        return $this->quoteApprovalRepository->update([
            'delivery_status' => QuoteApproval::DELIVERY_SENT,
        ], $approval->id);
    }

    public function convertToOrder(Quote $quote, ?string $orderReference = null): QuoteApproval
    {
        $approval = $this->forQuote($quote);

        if ($approval->status !== QuoteApproval::STATUS_APPROVED) {
            throw new InvalidArgumentException('Only approved quotes can be converted.');
        }

        return $this->quoteApprovalRepository->update([
            'delivery_status' => QuoteApproval::DELIVERY_CONVERTED,
            'order_reference' => $orderReference ?: ('ORD-'.$quote->id.'-'.now()->format('YmdHis')),
            'converted_at' => now(),
        ], $approval->id);
    }

    private function review(Quote $quote, string $status, ?string $notes): QuoteApproval
    {
        $approval = $this->forQuote($quote);

        if ($approval->status !== QuoteApproval::STATUS_PENDING) {
            throw new InvalidArgumentException('Only pending quotes can be reviewed.');
        }

        return $this->quoteApprovalRepository->update([
            'status' => $status,
            'reviewed_by' => Auth::guard('user')->id(),
            'reviewed_at' => now(),
            'notes' => $notes ?? $approval->notes,
        ], $approval->id);
    }
}
