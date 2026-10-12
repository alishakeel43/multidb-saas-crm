<?php

namespace Webkul\Telephony\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Webkul\Telephony\Repositories\CallLogRepository;
use Webkul\Telephony\Services\TelephonyService;
use Webkul\Telephony\Services\TenantTelephonyRouter;

class WebhookController extends Controller
{
    public function __construct(
        protected TelephonyService $telephonyService,
        protected TenantTelephonyRouter $tenantRouter,
        protected CallLogRepository $callLogRepository
    ) {}

    /**
     * Handle Twilio call-status webhook events.
     *
     * Flow:
     *  1. Validate the HMAC signature (abort 403 on failure).
     *  2. Extract the destination number and resolve the owning tenant.
     *  3. Run the status-update inside that tenant's DB context.
     *  4. Always return HTTP 200 — Twilio will retry on non-2xx.
     */
    public function handle(Request $request): JsonResponse
    {
        // 1 — validate signature (throws 403 on failure)
        $payload = $this->telephonyService->parseAndValidateWebhook($request);

        // 2 — identify target tenant from the "To" number
        $to = (string) ($payload['To'] ?? '');

        $tenant = $this->tenantRouter->resolveTenant($to);

        if (! $tenant) {
            Log::warning('Telephony webhook received for unknown/inactive number.', [
                'to' => $to,
            ]);

            // Twilio requires a 2xx response to stop retrying; log and acknowledge.
            return response()->json(['status' => 'UNKNOWN_TENANT'], 200);
        }

        // 3 — process inside tenant context
        $tenant->execute(fn () => $this->processStatusEvent($payload));

        return response()->json(['status' => 'OK'], 200);
    }

    /**
     * Return minimal TwiML for outbound calls.
     *
     * Twilio fetches this URL when the called party answers. It is intentionally
     * simple — a real deployment should serve richer TwiML or connect to a SIP
     * endpoint here.  Browser-based WebRTC calling is not implemented.
     */
    public function handleTwiml(Request $request): Response
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            .'<Response><Say>Connecting. Please wait.</Say></Response>';

        return response($xml, 200)
            ->header('Content-Type', 'text/xml');
    }

    /**
     * Handle Twilio recording-status webhook events.
     *
     * Validates the signature then upserts recording metadata on the matching
     * CallLog record.
     */
    public function recording(Request $request): JsonResponse
    {
        $payload = $this->telephonyService->parseAndValidateWebhook($request);

        $callSid = (string) ($payload['CallSid'] ?? '');
        $recordingSid = (string) ($payload['RecordingSid'] ?? '');
        $recordingUrl = (string) ($payload['RecordingUrl'] ?? '');

        if ($callSid === '' || $recordingSid === '') {
            return response()->json(['status' => 'MISSING_FIELDS'], 200);
        }

        $to = (string) ($payload['To'] ?? '');

        $tenant = $this->tenantRouter->resolveTenant($to);

        if (! $tenant) {
            Log::warning('Telephony recording webhook received for unknown/inactive number.', [
                'to' => $to,
            ]);

            return response()->json(['status' => 'UNKNOWN_TENANT'], 200);
        }

        $tenant->execute(function () use ($callSid, $recordingSid, $recordingUrl) {
            $callLog = $this->callLogRepository->findOneWhere([
                'provider_call_id' => $callSid,
            ]);

            if ($callLog) {
                $this->callLogRepository->update([
                    'recording_sid' => $recordingSid,
                    'recording_url' => $recordingUrl,
                ], $callLog->id);
            }
        });

        return response()->json(['status' => 'OK'], 200);
    }

    /**
     * Upsert a CallLog from a Twilio call-status callback payload.
     *
     * Must be called inside an active tenant context.
     */
    private function processStatusEvent(array $payload): void
    {
        $callSid = (string) ($payload['CallSid'] ?? '');

        if ($callSid === '') {
            return;
        }

        $status = strtolower((string) ($payload['CallStatus'] ?? ''));
        $from = (string) ($payload['From'] ?? '');
        $to = (string) ($payload['To'] ?? '');
        $direction = strtolower((string) ($payload['Direction'] ?? 'inbound'));
        $duration = isset($payload['CallDuration']) ? (int) $payload['CallDuration'] : null;

        $startedAt = null;

        if (! empty($payload['StartTime'])) {
            try {
                $startedAt = \Carbon\Carbon::parse($payload['StartTime']);
            } catch (\Throwable) {
                $startedAt = null;
            }
        }

        $endedAt = null;

        if (! empty($payload['EndTime'])) {
            try {
                $endedAt = \Carbon\Carbon::parse($payload['EndTime']);
            } catch (\Throwable) {
                $endedAt = null;
            }
        }

        // Normalise Twilio direction strings (outbound-api, outbound-dial → outbound)
        if (str_starts_with($direction, 'outbound')) {
            $direction = 'outbound';
        } else {
            $direction = 'inbound';
        }

        $existing = $this->callLogRepository->findOneWhere([
            'provider_call_id' => $callSid,
        ]);

        $data = array_filter([
            'status' => $status,
            'from_number' => $from,
            'to_number' => $to,
            'direction' => $direction,
            'duration_seconds' => $duration,
            'started_at' => $startedAt,
            'ended_at' => $endedAt,
            'provider_payload' => $payload,
        ], fn ($v) => $v !== null);

        if ($existing) {
            $this->callLogRepository->update($data, $existing->id);
        } else {
            $data['provider_call_id'] = $callSid;
            $this->callLogRepository->create($data);
        }
    }
}
