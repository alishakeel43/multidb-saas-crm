<?php

namespace App\Filament\Pages\Auth;

use App\Models\PlatformUser;
use App\Services\PlatformMfaService;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Pages\SimplePage;
use Illuminate\Validation\ValidationException;

/**
 * @property Form $form
 */
class MfaChallenge extends SimplePage
{
    protected static string $view = 'filament.pages.auth.mfa-challenge';

    protected static ?string $title = 'Two-factor authentication';

    protected static bool $shouldRegisterNavigation = false;

    public ?array $data = [];

    public function mount(): void
    {
        if (Filament::auth()->check()) {
            redirect()->intended(Filament::getUrl());
        }

        if (! session()->has('platform_mfa_user_id')) {
            redirect()->route('filament.super-admin.auth.login');
        }

        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('code')
                    ->label('Authentication code')
                    ->helperText('Enter a code from your authenticator app or a recovery code.')
                    ->required()
                    ->autocomplete('one-time-code'),
            ])
            ->statePath('data');
    }

    public function authenticate(PlatformMfaService $mfa): ?LoginResponse
    {
        $user = PlatformUser::query()->find(session('platform_mfa_user_id'));

        if (! $user || ! $user->is_active) {
            session()->forget(['platform_mfa_user_id', 'platform_mfa_remember']);

            throw ValidationException::withMessages([
                'data.code' => 'Your session expired. Please sign in again.',
            ]);
        }

        $code = (string) ($this->form->getState()['code'] ?? '');

        $valid = $mfa->verify($user, $code) || $mfa->consumeRecoveryCode($user, $code);

        if (! $valid) {
            throw ValidationException::withMessages([
                'data.code' => 'Invalid authentication code.',
            ]);
        }

        Filament::auth()->login($user, (bool) session('platform_mfa_remember', false));
        session()->forget(['platform_mfa_user_id', 'platform_mfa_remember']);
        session()->regenerate();

        return app(LoginResponse::class);
    }
}
