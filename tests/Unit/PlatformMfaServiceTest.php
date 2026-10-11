<?php

use App\Models\PlatformUser;
use App\Services\PlatformMfaService;
use PragmaRX\Google2FA\Google2FA;
use Tests\Support\LandlordSchema;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    LandlordSchema::configureMemoryConnections();
    LandlordSchema::migrate();

    $this->user = PlatformUser::query()->create([
        'name' => 'Admin',
        'email' => 'admin@example.test',
        'password' => 'password-password',
        'is_active' => true,
    ]);
});

it('enables MFA after a valid confirmation code', function () {
    $mfa = app(PlatformMfaService::class);
    $setup = $mfa->beginSetup($this->user->fresh());

    $code = app(Google2FA::class)->getCurrentOtp($setup['secret']);
    $recovery = $mfa->confirmSetup($this->user->fresh(), $code);

    expect($this->user->fresh()->hasTwoFactorEnabled())->toBeTrue()
        ->and($recovery)->toHaveCount(8)
        ->and($mfa->consumeRecoveryCode($this->user->fresh(), $recovery[0]))->toBeTrue()
        ->and($this->user->fresh()->two_factor_recovery_codes)->toHaveCount(7);
});
