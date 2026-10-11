<?php

namespace App\Services;

use App\Models\PlatformUser;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;
use RuntimeException;

class PlatformMfaService
{
    public function __construct(
        private readonly Google2FA $google2fa,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function beginSetup(PlatformUser $user): array
    {
        $secret = $this->google2fa->generateSecretKey();

        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_confirmed_at' => null,
            'two_factor_recovery_codes' => null,
        ])->save();

        $otpAuthUrl = $this->google2fa->getQRCodeUrl(
            config('app.name', 'Krayin SaaS'),
            $user->email,
            $secret,
        );

        return [
            'secret' => $secret,
            'qr_svg' => $this->qrSvg($otpAuthUrl),
        ];
    }

    public function confirmSetup(PlatformUser $user, string $code): array
    {
        if (! filled($user->two_factor_secret)) {
            throw new RuntimeException('MFA setup has not been started.');
        }

        if (! $this->verify($user, $code)) {
            throw new RuntimeException('Invalid authentication code.');
        }

        $recoveryCodes = Collection::times(8, fn () => Str::lower(Str::random(10)).'-'.Str::lower(Str::random(10)))
            ->values()
            ->all();

        $user->forceFill([
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => $recoveryCodes,
        ])->save();

        $this->auditLogger->log('platform_user.mfa_enabled', [
            'platform_user_id' => $user->id,
        ], $user, 'mfa_enabled');

        return $recoveryCodes;
    }

    public function disable(PlatformUser $user): void
    {
        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        $this->auditLogger->log('platform_user.mfa_disabled', [
            'platform_user_id' => $user->id,
        ], $user, 'mfa_disabled');
    }

    public function verify(PlatformUser $user, string $code): bool
    {
        if (! filled($user->two_factor_secret)) {
            return false;
        }

        return (bool) $this->google2fa->verifyKey($user->two_factor_secret, $code);
    }

    public function consumeRecoveryCode(PlatformUser $user, string $code): bool
    {
        $codes = $user->two_factor_recovery_codes ?? [];
        $normalized = strtolower(trim($code));

        if (! in_array($normalized, array_map('strtolower', $codes), true)) {
            return false;
        }

        $remaining = array_values(array_filter(
            $codes,
            fn (string $existing): bool => strtolower($existing) !== $normalized,
        ));

        $user->forceFill(['two_factor_recovery_codes' => $remaining])->save();

        return true;
    }

    private function qrSvg(string $otpAuthUrl): string
    {
        $renderer = new ImageRenderer(new RendererStyle(220), new SvgImageBackEnd);
        $writer = new Writer($renderer);

        return $writer->writeString($otpAuthUrl);
    }
}
