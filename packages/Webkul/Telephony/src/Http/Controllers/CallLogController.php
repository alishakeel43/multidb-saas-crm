<?php

namespace Webkul\Telephony\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Webkul\DataGrid\Facades\DataGrid;
use Webkul\Telephony\DataGrids\CallLogDataGrid;
use Webkul\Telephony\Repositories\CallLogRepository;
use Webkul\Telephony\Services\TelephonyService;

class CallLogController extends Controller
{
    public function __construct(
        protected TelephonyService $telephonyService,
        protected CallLogRepository $callLogRepository
    ) {}

    /**
     * Display call-log DataGrid or return JSON response for the datagrid.
     */
    public function index(Request $request): mixed
    {
        abort_unless(bouncer()->hasPermission('telephony.call_logs.view'), 403);

        if ($request->ajax()) {
            return DataGrid::of(CallLogDataGrid::class)->toJson();
        }

        return view('telephony::call-logs.index');
    }

    /**
     * Initiate an outbound call and create a CallLog record.
     */
    public function store(Request $request): JsonResponse
    {
        abort_unless(bouncer()->hasPermission('telephony.call_logs.create'), 403);

        $validated = $request->validate([
            'to' => ['required', 'string', 'max:30'],
        ]);

        $from = (string) core()->getConfigData('telephony.credentials.from_number');

        if ($from === '') {
            return response()->json([
                'error' => __('telephony::app.call-logs.errors.no-from-number'),
            ], 422);
        }

        $statusCallbackUrl = $this->resolveStatusCallbackUrl();

        try {
            $callLog = $this->telephonyService->initiateCall(
                to: $validated['to'],
                from: $from,
                statusCallbackUrl: $statusCallbackUrl,
                userId: auth()->id()
            );
        } catch (\Throwable $e) {
            Log::error('Telephony: outbound call initiation failed.', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => __('telephony::app.call-logs.errors.initiation-failed'),
            ], 503);
        }

        return response()->json([
            'message' => __('telephony::app.call-logs.call-initiated'),
            'call_log' => [
                'id' => $callLog->id,
                'provider_call_id' => $callLog->provider_call_id,
                'status' => $callLog->status,
            ],
        ], 201);
    }

    /**
     * Soft-delete a call log record.
     */
    public function destroy(int $id): JsonResponse
    {
        abort_unless(bouncer()->hasPermission('telephony.call_logs.delete'), 403);

        $callLog = $this->callLogRepository->findOrFail($id);

        $this->callLogRepository->delete($id);

        return response()->json([
            'message' => __('telephony::app.call-logs.deleted'),
        ]);
    }

    /**
     * Proxy or redirect to a recording for the given call log.
     *
     * The raw Twilio recording URL (which embeds account credentials) is never
     * sent to the browser.  Instead we issue a short-lived signed redirect so the
     * browser fetches the media directly from Twilio using a temporary URL.
     */
    public function recordingProxy(int $id): RedirectResponse|JsonResponse
    {
        abort_unless(bouncer()->hasPermission('telephony.call_logs.view'), 403);

        $callLog = $this->callLogRepository->findOrFail($id);

        if (! $callLog->recording_url) {
            return response()->json([
                'error' => __('telephony::app.call-logs.errors.no-recording'),
            ], 404);
        }

        // Append .mp3 so browsers open the media player directly; the recording_url
        // stored from Twilio already contains the authenticated path segment.
        $mediaUrl = rtrim($callLog->recording_url, '/').'.mp3';

        return redirect()->away($mediaUrl);
    }

    /**
     * Build the public status-callback URL Twilio will POST to.
     */
    private function resolveStatusCallbackUrl(): string
    {
        $override = (string) core()->getConfigData('telephony.credentials.status_callback_base_url');

        $base = $override !== '' ? rtrim($override, '/') : rtrim((string) config('app.url'), '/');

        return $base.'/telephony/webhook';
    }
}
