<?php

namespace Webkul\QuoteApproval\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Quote\Repositories\QuoteRepository;
use Webkul\QuoteApproval\Models\QuoteApproval;
use Webkul\QuoteApproval\Services\QuoteApprovalService;

class QuoteApprovalController extends Controller
{
    public function __construct(
        protected QuoteApprovalService $approvals,
        protected QuoteRepository $quoteRepository,
    ) {}

    public function index(): View
    {
        return view('quote_approval::index', [
            'approvals' => QuoteApproval::query()
                ->with(['quote', 'requester', 'reviewer'])
                ->latest('updated_at')
                ->limit(100)
                ->get(),
        ]);
    }

    public function submit(Request $request, int $quoteId): RedirectResponse
    {
        return $this->act($quoteId, fn ($quote) => $this->approvals->submit($quote, $request->input('notes')));
    }

    public function approve(Request $request, int $quoteId): RedirectResponse
    {
        return $this->act($quoteId, fn ($quote) => $this->approvals->approve($quote, $request->input('notes')));
    }

    public function reject(Request $request, int $quoteId): RedirectResponse
    {
        return $this->act($quoteId, fn ($quote) => $this->approvals->reject($quote, $request->input('notes')));
    }

    private function act(int $quoteId, callable $callback): RedirectResponse
    {
        try {
            $quote = $this->quoteRepository->findOrFail($quoteId);
            $callback($quote);
            session()->flash('success', trans('quote_approval::app.messages.updated'));
        } catch (Throwable $exception) {
            session()->flash('error', $exception->getMessage());
        }

        return redirect()->route('admin.quotes.approvals.index');
    }
}
