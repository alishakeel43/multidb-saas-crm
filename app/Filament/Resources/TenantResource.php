<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TenantResource\Pages;
use App\Models\Domain;
use App\Models\Tenant;
use App\Services\StorageQuotaService;
use App\Services\TenantOverviewService;
use App\Services\TenantSeedService;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Throwable;

class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationGroup = 'Tenants & billing';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('slug')
                    ->required()
                    ->maxLength(100)
                    ->unique(ignoreRecord: true)
                    ->visibleOn('create')
                    ->live(debounce: 400)
                    ->afterStateUpdated(function (string $state, Set $set) {
                        $baseDomain = config('multitenancy.tenant_base_domain', 'localhost');
                        $slug = strtolower(trim($state));

                        if ($slug !== '') {
                            $set('domain', "{$slug}.{$baseDomain}");
                        }
                    }),
                // Hidden — carries computed domain value on form submit
                TextInput::make('domain')
                    ->required()
                    ->maxLength(255)
                    ->unique(Domain::class, 'domain')
                    ->hidden()
                    ->visibleOn('create'),

                // Visual read-only span shown below the slug field
                Placeholder::make('domain_preview')
                    ->label('Primary domain')
                    ->content(function (Get $get, ?Tenant $record): string {
                        if ($record !== null) {
                            return $record->domains->first()?->domain ?? '—';
                        }

                        return $get('domain') ?: 'Type a slug above';
                    })
                    ->visibleOn(['create', 'edit']),

                TextInput::make('admin_name')
                    ->label('Tenant administrator name')
                    ->required()
                    ->maxLength(255)
                    ->visibleOn('create'),
                TextInput::make('admin_email')
                    ->label('Tenant administrator email')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->visibleOn('create'),
                TextInput::make('admin_password')
                    ->label('Initial tenant administrator password')
                    ->password()
                    ->revealable(false)
                    ->required()
                    ->minLength(12)
                    ->visibleOn('create'),
                Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'active' => 'Active',
                        'suspended' => 'Suspended',
                        'canceled' => 'Canceled',
                    ])
                    ->required()
                    ->visibleOn('edit'),
                Select::make('plan_id')
                    ->relationship('plan', 'name')
                    ->searchable()
                    ->preload()
                    ->visibleOn('edit'),
                DateTimePicker::make('trial_ends_at')
                    ->visibleOn('edit'),
                TextInput::make('storage_quota_mb')
                    ->label('Storage quota override (MB)')
                    ->numeric()
                    ->minValue(0)
                    ->visibleOn('edit'),
                Placeholder::make('storage_usage')
                    ->label('Storage usage')
                    ->content(function (?Tenant $record): string {
                        if (! $record) {
                            return '—';
                        }

                        $summary = app(StorageQuotaService::class)->summary($record);

                        return "{$summary['used_mb']} MB / {$summary['quota_mb']} MB ({$summary['percent']}%)";
                    })
                    ->visibleOn('edit'),
                TextInput::make('stripe_customer_id')
                    ->label('Stripe customer ID')
                    ->disabled()
                    ->dehydrated(false)
                    ->visibleOn('edit'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Workspace')
                    ->description(fn (Tenant $record): string => $record->slug)
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),
                TextColumn::make('domains')
                    ->label('Domains')
                    ->getStateUsing(fn (Tenant $record): array => $record->domains->pluck('domain')->all())
                    ->listWithLineBreaks()
                    ->limitList(2)
                    ->expandableLimitedList()
                    ->placeholder('No domain'),
                TextColumn::make('tenant_administrator')
                    ->label('Administrator / owner')
                    ->getStateUsing(fn (Tenant $record): string => app(TenantOverviewService::class)
                        ->detailsFor($record)['owner_name'] ?? 'Not identified')
                    ->description(fn (Tenant $record): ?string => app(TenantOverviewService::class)
                        ->detailsFor($record)['owner_email'])
                    ->placeholder('Unavailable'),
                TextColumn::make('crm_users')
                    ->label('Users')
                    ->alignEnd()
                    ->getStateUsing(fn (Tenant $record): ?int => app(TenantOverviewService::class)
                        ->detailsFor($record)['total_users'])
                    ->numeric()
                    ->sortable(false)
                    ->placeholder('—'),
                TextColumn::make('plan.name')->label('Plan')->placeholder('None'),
                TextColumn::make('status')
                    ->badge()
                    ->sortable()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'pending' => 'warning',
                        'suspended', 'canceled' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('trial_ends_at')
                    ->label('Trial ends')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                ViewAction::make()
                    ->modalHeading(fn (Tenant $record): string => "Tenant details: {$record->name}")
                    ->modalWidth('4xl')
                    ->infolist(fn (Infolist $infolist): Infolist => $infolist
                        ->schema([
                            Section::make('Workspace')
                                ->columns(2)
                                ->schema([
                                    TextEntry::make('name')->label('Tenant name'),
                                    TextEntry::make('slug')->badge(),
                                    TextEntry::make('domains')
                                        ->label('Domains')
                                        ->state(fn (Tenant $record): string => $record->domains->pluck('domain')->join(', '))
                                        ->placeholder('No domain configured'),
                                    TextEntry::make('plan.name')->label('Plan')->placeholder('No plan assigned'),
                                    TextEntry::make('status')->badge(),
                                    TextEntry::make('created_at')->dateTime(),
                                    TextEntry::make('trial_ends_at')->label('Trial ends')->dateTime()->placeholder('Not on trial'),
                                    TextEntry::make('subscription_status')
                                        ->label('Latest subscription')
                                        ->state(fn (Tenant $record): ?string => $record->subscriptions->sortByDesc('created_at')->first()?->status)
                                        ->badge()
                                        ->placeholder('No subscription'),
                                ]),
                            Section::make('Primary administrator / owner contact')
                                ->description('Contact information is read from the tenant CRM database.')
                                ->columns(2)
                                ->schema([
                                    TextEntry::make('owner_name')
                                        ->label('Name')
                                        ->state(fn (Tenant $record): string => app(TenantOverviewService::class)
                                            ->detailsFor($record)['owner_name'] ?? 'Administrator not identified'),
                                    TextEntry::make('owner_email')
                                        ->label('Email')
                                        ->icon('heroicon-m-envelope')
                                        ->state(fn (Tenant $record): ?string => app(TenantOverviewService::class)
                                            ->detailsFor($record)['owner_email'])
                                        ->placeholder('No administrator email available'),
                                    TextEntry::make('crm_database_status')
                                        ->label('CRM database')
                                        ->state(fn (Tenant $record): string => app(TenantOverviewService::class)
                                            ->detailsFor($record)['database_status'])
                                        ->badge()
                                        ->color(fn (string $state): string => $state === 'Connected' ? 'success' : 'danger'),
                                    TextEntry::make('crm_database_message')
                                        ->label('Connection note')
                                        ->state(fn (Tenant $record): ?string => app(TenantOverviewService::class)
                                            ->detailsFor($record)['database_message'])
                                        ->placeholder('Database connection is healthy'),
                                ]),
                            Section::make('Support accounts')
                                ->description('Managed separately from regular tenant CRM users.')
                                ->schema([
                                    TextEntry::make('support_users')
                                        ->label('Name and email')
                                        ->state(fn (Tenant $record): string => collect(app(TenantOverviewService::class)
                                            ->detailsFor($record)['support_users'])
                                            ->map(fn (array $user): string => "{$user['name']} <{$user['email']}>")
                                            ->join(', '))
                                        ->placeholder('No support accounts configured')
                                        ->columnSpanFull(),
                                ]),
                            Section::make('CRM workspace usage')
                                ->columns(3)
                                ->schema([
                                    TextEntry::make('total_users')
                                        ->label('Total users')
                                        ->numeric()
                                        ->state(fn (Tenant $record): ?int => app(TenantOverviewService::class)
                                            ->detailsFor($record)['total_users'])
                                        ->placeholder('Unavailable'),
                                    TextEntry::make('active_users')
                                        ->label('Active users')
                                        ->numeric()
                                        ->state(fn (Tenant $record): ?int => app(TenantOverviewService::class)
                                            ->detailsFor($record)['active_users'])
                                        ->placeholder('Unavailable'),
                                    TextEntry::make('leads')
                                        ->label('Leads')
                                        ->numeric()
                                        ->state(fn (Tenant $record): ?int => app(TenantOverviewService::class)
                                            ->detailsFor($record)['leads'])
                                        ->placeholder('Unavailable'),
                                    TextEntry::make('contacts')
                                        ->label('Contacts')
                                        ->numeric()
                                        ->state(fn (Tenant $record): ?int => app(TenantOverviewService::class)
                                            ->detailsFor($record)['contacts'])
                                        ->placeholder('Unavailable'),
                                    TextEntry::make('organizations')
                                        ->label('Organizations')
                                        ->numeric()
                                        ->state(fn (Tenant $record): ?int => app(TenantOverviewService::class)
                                            ->detailsFor($record)['organizations'])
                                        ->placeholder('Unavailable'),
                                ]),
                        ])),
                EditAction::make(),
                ActionGroup::make([
                    Action::make('seedBaseline')
                        ->label('Seed CRM baseline')
                        ->icon('heroicon-m-arrow-path')
                        ->requiresConfirmation()
                        ->modalHeading('Seed CRM baseline')
                        ->modalDescription(fn (Tenant $record): string => "Run TenantCrmSeeder for {$record->slug}? Safe to re-run.")
                        ->action(function (Tenant $record): void {
                            self::runSeedAction($record, false);
                        }),
                    Action::make('seedDemo')
                        ->label('Seed demo + P0 data')
                        ->icon('heroicon-m-beaker')
                        ->color('primary')
                        ->requiresConfirmation()
                        ->modalHeading('Seed demo CRM data')
                        ->modalDescription(fn (Tenant $record): string => "Add sample CRM + P0 records for {$record->slug}? Does not delete existing data.")
                        ->action(function (Tenant $record): void {
                            self::runSeedAction($record, true);
                        }),
                ])
                    ->label('Seeders')
                    ->icon('heroicon-m-circle-stack')
                    ->button()
                    ->color('gray'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function runSeedAction(Tenant $record, bool $demo): void
    {
        try {
            app(TenantSeedService::class)->seed($record, $demo);

            Notification::make()
                ->title($demo
                    ? "Demo seed completed for {$record->slug}"
                    : "Baseline seed completed for {$record->slug}")
                ->success()
                ->send();
        } catch (Throwable $exception) {
            Notification::make()
                ->title("Seed failed for {$record->slug}")
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['domains', 'plan', 'subscriptions']);
    }

    public static function getRelations(): array
    {
        return [
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTenants::route('/'),
            'create' => Pages\CreateTenant::route('/create'),
            'edit' => Pages\EditTenant::route('/{record}/edit'),
        ];
    }
}
