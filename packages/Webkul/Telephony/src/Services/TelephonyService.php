<?php

namespace Webkul\Telephony\Services;

use Illuminate\Http\Request;
use Twilio\Security\RequestValidator;
use Webkul\Telephony\Models\CallLog;
use Webkul\Telephony\Repositories\CallLogRepository;

class TelephonyService
{
    public function __construct(
        protected TwilioClientFactory $clientFactory,
        protected CallLogRepository $callLogRepository
    ) {}

    /**
     * Initiate an outbound call via Twilio and persist a CallLog record.
     *
     * The TwiML URL instructs Twilio what to do once the call connects; it
     * must be a publicly reachable URL served by this application.
     */
    public function initiateCall(
        string $to,
        string $from,
        string $statusCallbackUrl,
        int $userId
    ): CallLog {
        $client = $this->clientFactory->make();

        $twimlUrl = route('telephony.twiml');

        $twilioCall = $client->calls->create($to, $from, [
            'url' => $twimlUrl,
            'statusCallback' => $statusCallbackUrl,
            'statusCallbackMethod' => 'POST',
            'statusCallbackEvent' => ['initiated', 'ringing', 'answered', 'completed'],
        ]);

        return $this->callLogRepository->create([
            'provider_call_id' => $twilioCall->sid,
            'direction' => 'outbound',
            'status' => 'initiated',
            'from_number' => $from,
            'to_number' => $to,
            'user_id' => $userId,
        ]);
    }

    /**
     * Fetch full call details from Twilio for a given CallSid.
     */
    public function getCallDetails(string $callSid): array
    {
        $client = $this->clientFactory->make();

        $call = $client->calls($callSid)->fetch();

        return [
            'sid' => $call->sid,
            'status' => $call->status,
            'direction' => $call->direction,
            'from' => $call->from,
            'to' => $call->to,
            'duration' => $call->duration,
            'start_time' => $call->startTime,
            'end_time' => $call->endTime,
        ];
    }

    /**
     * Fetch recording metadata for a call from Twilio.
     *
     * Returns an array of recording metadata arrays; never exposes credentials.
     */
    public function fetchRecordingMetadata(string $callSid): array
    {
        $client = $this->clientFactory->make();

        $recordings = $client->recordings->read(['callSid' => $callSid]);

        return array_map(fn ($rec) => [
            'sid' => $rec->sid,
            'call_sid' => $rec->callSid,
            'duration' => $rec->duration,
            'date_created' => $rec->dateCreated,
            'status' => $rec->status,
        ], $recordings);
    }

    /**
     * Validate the Twilio webhook HMAC signature and return the payload.
     *
     * Aborts with 403 if signature validation is enabled and the signature
     * is missing or invalid. Credentials are never logged or returned.
     */
    public function parseAndValidateWebhook(Request $request): array
    {
        $signingEnabled = (bool) core()->getConfigData('telephony.credentials.webhook_signing_enabled');

        if ($signingEnabled) {
            $authToken = (string) (core()->getConfigData('telephony.credentials.auth_token') ?? env('TWILIO_AUTH_TOKEN', ''));

            abort_if($authToken === '', 503, 'Twilio webhook signing is not configured.');

            $signature = (string) $request->header('X-Twilio-Signature', '');

            $validator = new RequestValidator($authToken);

            $url = $request->fullUrl();

            $params = $request->isJson()
                ? (array) $request->json()->all()
                : $request->post();

            abort_unless($validator->validate($signature, $url, $params), 403);
        }

        return $request->all();
    }
}
