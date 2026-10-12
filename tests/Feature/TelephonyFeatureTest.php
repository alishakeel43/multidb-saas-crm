<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Twilio\Security\RequestValidator;
use Webkul\Telephony\Repositories\CallLogRepository;
use Webkul\Telephony\Services\TelephonyService;
use Webkul\Telephony\Services\TwilioClientFactory;

beforeEach(function () {
    Schema::dropIfExists('call_logs');

    Schema::create('call_logs', function ($table) {
        $table->id();
        $table->string('provider_call_id', 191)->unique();
        $table->enum('direction', ['inbound', 'outbound']);
        $table->string('status', 50);
        $table->string('from_number', 30)->nullable();
        $table->string('to_number', 30)->nullable();
        $table->unsignedBigInteger('contact_id')->nullable();
        $table->unsignedBigInteger('lead_id')->nullable();
        $table->unsignedBigInteger('user_id')->nullable();
        $table->unsignedInteger('duration_seconds')->nullable();
        $table->text('recording_url')->nullable();
        $table->string('recording_sid', 191)->nullable();
        $table->timestamp('started_at')->nullable();
        $table->timestamp('ended_at')->nullable();
        $table->json('provider_payload')->nullable();
        $table->timestamps();
    });

    DB::table('core_config')->whereIn('code', [
        'telephony.credentials.account_sid',
        'telephony.credentials.auth_token',
        'telephony.credentials.from_number',
        'telephony.credentials.webhook_signing_enabled',
    ])->delete();

    DB::table('core_config')->insert([
        ['code' => 'telephony.credentials.account_sid', 'value' => 'AC1234567890', 'created_at' => now(), 'updated_at' => now()],
        ['code' => 'telephony.credentials.auth_token', 'value' => 'test-auth-token', 'created_at' => now(), 'updated_at' => now()],
        ['code' => 'telephony.credentials.from_number', 'value' => '+15550009999', 'created_at' => now(), 'updated_at' => now()],
        ['code' => 'telephony.credentials.webhook_signing_enabled', 'value' => '1', 'created_at' => now(), 'updated_at' => now()],
    ]);
});

it('accepts a valid Twilio webhook signature and returns the payload', function () {
    $service = app(TelephonyService::class);
    $url = 'http://localhost/telephony/webhook';
    $payload = [
        'CallSid' => 'CA123',
        'CallStatus' => 'ringing',
        'To' => '+15550007777',
        'From' => '+15550008888',
        'Direction' => 'inbound',
    ];
    $validator = new RequestValidator('test-auth-token');
    $signature = $validator->computeSignature($url, $payload);

    $request = Request::create($url, 'POST', $payload, [], [], [
        'HTTP_X_TWILIO_SIGNATURE' => $signature,
    ]);

    expect($service->parseAndValidateWebhook($request))->toMatchArray($payload);
});

it('rejects invalid Twilio webhook signatures with a 403', function () {
    $service = app(TelephonyService::class);
    $url = 'http://localhost/telephony/webhook';
    $payload = [
        'CallSid' => 'CA999',
        'CallStatus' => 'ringing',
        'To' => '+15550007777',
        'From' => '+15550008888',
    ];

    $request = Request::create($url, 'POST', $payload, [], [], [
        'HTTP_X_TWILIO_SIGNATURE' => 'invalid-signature',
    ]);

    expect(fn () => $service->parseAndValidateWebhook($request))
        ->toThrow(HttpException::class);
});

it('initiates an outbound call using the configured Twilio parameters', function () {
    $factory = Mockery::mock(TwilioClientFactory::class);
    $calls = Mockery::mock();
    $client = new class('AC123', 'secret') extends \Twilio\Rest\Client {
        public $callsMock;

        public function __get($name)
        {
            if ($name === 'calls') {
                return $this->callsMock;
            }

            return parent::__get($name);
        }
    };

    $client->callsMock = $calls;

    $calls->shouldReceive('create')->once()->with(
        '+15550008888',
        '+15550009999',
        Mockery::on(function ($args) {
            expect($args)->toHaveKeys(['url', 'statusCallback', 'statusCallbackMethod', 'statusCallbackEvent'])
                ->and($args['url'])->toBe(route('telephony.twiml'))
                ->and($args['statusCallback'])->toBe('http://localhost/telephony/webhook')
                ->and($args['statusCallbackMethod'])->toBe('POST')
                ->and($args['statusCallbackEvent'])->toBe(['initiated', 'ringing', 'answered', 'completed']);

            return true;
        })
    )->andReturn((object) ['sid' => 'CA_OUTBOUND_123']);

    $factory->shouldReceive('make')->once()->andReturn($client);

    $service = new TelephonyService($factory, app(CallLogRepository::class));

    $callLog = $service->initiateCall('+15550008888', '+15550009999', 'http://localhost/telephony/webhook', getDefaultAdmin()->id);

    expect($callLog)->not->toBeNull()
        ->and($callLog->provider_call_id)->toBe('CA_OUTBOUND_123')
        ->and($callLog->direction)->toBe('outbound')
        ->and($callLog->status)->toBe('initiated');
});

it('allows an authenticated admin to initiate an outbound call', function () {
    $admin = getDefaultAdmin();

    $factory = Mockery::mock(TwilioClientFactory::class);
    $calls = Mockery::mock();
    $client = new class('AC123', 'secret') extends \Twilio\Rest\Client {
        public $callsMock;

        public function __get($name)
        {
            if ($name === 'calls') {
                return $this->callsMock;
            }

            return parent::__get($name);
        }
    };

    $client->callsMock = $calls;

    $calls->shouldReceive('create')->once()->with(
        '+15550001111',
        '+15550009999',
        Mockery::type('array')
    )->andReturn((object) ['sid' => 'CA_ADMIN_OUTBOUND']);

    $factory->shouldReceive('make')->once()->andReturn($client);

    app()->instance(TwilioClientFactory::class, $factory);
    app()->instance(TelephonyService::class, new TelephonyService($factory, app(CallLogRepository::class)));

    $response = test()->actingAs($admin)
        ->postJson(route('admin.telephony.call_logs.store'), ['to' => '+15550001111']);

    $response->assertCreated();
    $this->assertDatabaseHas('call_logs', [
        'provider_call_id' => 'CA_ADMIN_OUTBOUND',
        'direction' => 'outbound',
        'status' => 'initiated',
        'to_number' => '+15550001111',
        'from_number' => '+15550009999',
    ]);
});
