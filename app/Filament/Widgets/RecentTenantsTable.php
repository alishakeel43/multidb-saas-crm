<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\TenantResource;
use App\Models\Tenant;
use App\Services\TenantOverviewService;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentTenantsTable extends TableWidget
{
    protected static ?int $sort = -1;

    protected int|string|array $columnSpan = 1;

    protected function getTableQuery(): Builder
    {
        return Tenant::query()
            ->with([
                'domains' => fn ($query) => $query
                    ->where('is_primary', true)
                    ->where('is_active', true),
                'plan',
            ])
            ->latest()
            ->limit(5);
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Recent tenants')
            ->description('Latest workspaces with their primary administrator and CRM seat usage.')
            ->columns([
                TextColumn::make('name')
                    ->label('Workspace')
                    ->description(fn (Tenant $record): string => $record->domains->first()?->domain ?? 'No active domain')
                    ->weight('semibold')
                    ->searchable(),
                TextColumn::make('administrator')
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
                    ->placeholder('—'),
                TextColumn::make('crm_database_status')
                    ->label('CRM database')
                    ->getStateUsing(fn (Tenant $record): string => app(TenantOverviewService::class)
                        ->detailsFor($record)['database_status'])
                    ->badge()
                    ->color(fn (string $state): string => $state === 'Connected' ? 'success' : 'danger'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'pending' => 'warning',
                        'suspended', 'canceled' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('manage')
                    ->label('Manage')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->url(fn (Tenant $record): string => TenantResource::getUrl('edit', [
                        'record' => $record,
                    ])),
            ])
            ->paginated(false);
    }
}
