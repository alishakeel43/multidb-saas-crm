<?php

namespace App\Filament\Pages;

use App\Services\PlatformMfaService;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;
use Throwable;

/**
 * @property Form $form
 */
class ManageTwoFactorAuthentication extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static string $view = 'filament.pages.manage-two-factor-authentication';

    protected static ?string $navigationGroup = 'Platform';

    protected static ?string $navigationLabel = 'Two-factor auth';

    protected static ?string $title = 'Two-factor authentication';

    protected static ?int $navigationSort = 40;

    protected ?string $heading = 'Two-factor authentication';

    protected ?string $subheading = 'Require an authenticator code after password login for Super Admin accounts.';

    public ?array $data = [];

    public ?string $qrSvg = null;

    public ?string $secret = null;

    /** @var list<string>|null */
    public ?array $recoveryCodes = null;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Status')
                    ->schema([
                        Placeholder::make('status')
                            ->label('Current state')
                            ->content(fn (): HtmlString => new HtmlString(
                                Auth::guard('platform')->user()?->hasTwoFactorEnabled()
                                    ? '<span class="saas-badge saas-badge--ok">Enabled</span>'
                                    : '<span class="saas-badge saas-badge--warning">Disabled</span>'
                            )),
                    ]),
                Section::make('Setup')
                    ->description('Start setup to generate a secret, then confirm with a code from your authenticator app.')
                    ->schema([
                        TextInput::make('secret')
                            ->label('Manual secret')
                            ->helperText('Use this if you cannot scan the QR code.')
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn (): bool => filled($this->secret)),
                        TextInput::make('code')
                            ->label('Confirmation code')
                            ->placeholder('123456')
                            ->autocomplete('one-time-code')
                            ->visible(fn (): bool => filled($this->secret) && blank(Auth::guard('platform')->user()?->two_factor_confirmed_at)),
                    ]),
            ])
            ->statePath('data');
    }

    public function beginSetup(PlatformMfaService $mfa): void
    {
        $result = $mfa->beginSetup(Auth::guard('platform')->user());
        $this->qrSvg = $result['qr_svg'];
        $this->secret = $result['secret'];
        $this->data['secret'] = $result['secret'];
        $this->recoveryCodes = null;

        Notification::make()
            ->title('Authenticator setup ready')
            ->body('Scan the QR code, then confirm with a one-time code.')
            ->success()
            ->send();
    }

    public function confirmSetup(PlatformMfaService $mfa): void
    {
        try {
            $this->recoveryCodes = $mfa->confirmSetup(
                Auth::guard('platform')->user(),
                (string) ($this->data['code'] ?? ''),
            );
            $this->qrSvg = null;
            $this->secret = null;
            $this->data['code'] = null;

            Notification::make()
                ->title('Two-factor authentication enabled')
                ->body('Store the recovery codes in a secure location.')
                ->success()
                ->send();
        } catch (Throwable $exception) {
            Notification::make()->title($exception->getMessage())->danger()->send();
        }
    }

    public function disable(PlatformMfaService $mfa): void
    {
        $mfa->disable(Auth::guard('platform')->user());
        $this->qrSvg = null;
        $this->secret = null;
        $this->recoveryCodes = null;

        Notification::make()
            ->title('Two-factor authentication disabled')
            ->warning()
            ->send();
    }
}
