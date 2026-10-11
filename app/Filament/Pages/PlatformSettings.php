<?php

namespace App\Filament\Pages;

use App\Services\PlatformSettingsRepository;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/**
 * @property Form $form
 */
class PlatformSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string $view = 'filament.pages.platform-settings';

    protected static ?string $navigationGroup = 'Platform';

    protected static ?string $navigationLabel = 'Settings';

    protected static ?string $title = 'Platform settings';

    protected static ?int $navigationSort = 20;

    protected ?string $heading = 'Platform settings';

    protected ?string $subheading = 'Shared defaults for branding, billing, security, and outbound notifications.';

    public ?array $data = [];

    public function mount(PlatformSettingsRepository $settings): void
    {
        $this->form->fill([
            'general_name' => $settings->get('general.name', config('app.name')),
            'general_support_email' => $settings->get('general.support_email', config('mail.from.address')),
            'billing_currency' => $settings->currency(),
            'billing_tax_percent' => $settings->taxPercent(),
            'billing_trial_days' => $settings->trialDays(),
            'security_enforce_subscription' => $settings->enforceSubscription(),
            'notifications_from_address' => $settings->get('notifications.from_address', config('mail.from.address')),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('General')
                    ->description('Identity shown across platform notifications and operator-facing labels.')
                    ->icon('heroicon-o-building-office')
                    ->columns(2)
                    ->schema([
                        TextInput::make('general_name')->label('Platform name')->required(),
                        TextInput::make('general_support_email')->label('Support email')->email()->required(),
                    ]),
                Section::make('Billing defaults')
                    ->description('Used when creating invoices and starting new tenant trials.')
                    ->icon('heroicon-o-banknotes')
                    ->columns(3)
                    ->schema([
                        TextInput::make('billing_currency')->label('Currency')->length(3)->required(),
                        TextInput::make('billing_tax_percent')->label('Tax %')->numeric()->minValue(0)->required(),
                        TextInput::make('billing_trial_days')->label('Trial days')->numeric()->minValue(0)->required(),
                    ]),
                Section::make('Security')
                    ->description('Gate tenant CRM access when subscriptions lapse.')
                    ->icon('heroicon-o-lock-closed')
                    ->schema([
                        Toggle::make('security_enforce_subscription')
                            ->label('Enforce active subscription for tenant CRM access')
                            ->inline(false),
                    ]),
                Section::make('Notifications')
                    ->description('Sender identity for platform lifecycle emails.')
                    ->icon('heroicon-o-envelope')
                    ->schema([
                        TextInput::make('notifications_from_address')->label('From address')->email()->required(),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(PlatformSettingsRepository $settings): void
    {
        $data = $this->form->getState();

        $settings->putMany([
            'general.name' => $data['general_name'],
            'general.support_email' => $data['general_support_email'],
            'billing.currency' => strtoupper($data['billing_currency']),
            'billing.tax_percent' => (float) $data['billing_tax_percent'],
            'billing.trial_days' => (int) $data['billing_trial_days'],
            'security.enforce_subscription' => (bool) $data['security_enforce_subscription'],
            'notifications.from_address' => $data['notifications_from_address'],
        ]);

        audit('platform.settings_updated', [
            'keys' => array_keys($data),
        ]);

        Notification::make()
            ->title('Platform settings saved')
            ->body('New tenants and billing flows will use these defaults.')
            ->success()
            ->send();
    }
}
