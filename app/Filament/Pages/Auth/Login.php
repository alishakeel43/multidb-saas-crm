<?php

namespace App\Filament\Pages\Auth;

use App\Models\PlatformUser;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Facades\Filament;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Models\Contracts\FilamentUser;
use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Validation\ValidationException;

class Login extends BaseLogin
{
    public function authenticate(): ?LoginResponse
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        $data = $this->form->getState();

        if (! Filament::auth()->attempt($this->getCredentialsFromFormData($data), $data['remember'] ?? false)) {
            $this->throwFailureValidationException();
        }

        $user = Filament::auth()->user();

        if (
            ($user instanceof FilamentUser && ! $user->canAccessPanel(Filament::getCurrentPanel()))
            || ! $user instanceof PlatformUser
        ) {
            Filament::auth()->logout();
            $this->throwFailureValidationException();
        }

        if ($user->hasTwoFactorEnabled()) {
            Filament::auth()->logout();

            session()->put('platform_mfa_user_id', $user->getKey());
            session()->put('platform_mfa_remember', (bool) ($data['remember'] ?? false));

            $this->redirect(route('filament.super-admin.auth.mfa-challenge'));

            return null;
        }

        session()->regenerate();

        return app(LoginResponse::class);
    }

    protected function throwFailureValidationException(): never
    {
        throw ValidationException::withMessages([
            'data.email' => __('filament-panels::pages/auth/login.messages.failed'),
        ]);
    }
}
