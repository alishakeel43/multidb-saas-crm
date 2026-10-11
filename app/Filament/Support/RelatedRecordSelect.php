<?php

namespace App\Filament\Support;

use App\Filament\Resources\PlanResource;
use App\Filament\Resources\TenantResource;
use App\Models\Plan;
use App\Models\Tenant;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Model;

class RelatedRecordSelect
{
    /**
     * Tenant dropdown with an always-visible “Create new tenant” escape hatch.
     */
    public static function tenant(string $name = 'tenant_id', string $relationship = 'tenant'): Select
    {
        return self::for(
            name: $name,
            relationship: $relationship,
            titleAttribute: 'name',
            modelClass: Tenant::class,
            createUrl: fn (): string => TenantResource::getUrl('create'),
            createLabel: 'Create new tenant',
            emptyHelperText: 'No tenants yet. Create a new tenant, then come back and select it here.',
            foundHelperText: 'Need a workspace that is not listed? Create a new tenant (opens in a new tab), then refresh this list.',
        );
    }

    /**
     * Plan dropdown with an always-visible “Create new plan” escape hatch.
     */
    public static function plan(string $name = 'plan_id', string $relationship = 'plan'): Select
    {
        return self::for(
            name: $name,
            relationship: $relationship,
            titleAttribute: 'name',
            modelClass: Plan::class,
            createUrl: fn (): string => PlanResource::getUrl('create'),
            createLabel: 'Create new plan',
            emptyHelperText: 'No plans yet. Create a new plan, then come back and select it here.',
            foundHelperText: 'Need a plan that is not listed? Create a new plan (opens in a new tab), then refresh this list.',
        );
    }

    /**
     * @param  class-string<Model>  $modelClass
     * @param  \Closure(): string  $createUrl
     */
    public static function for(
        string $name,
        string $relationship,
        string $titleAttribute,
        string $modelClass,
        \Closure $createUrl,
        string $createLabel,
        string $emptyHelperText,
        string $foundHelperText,
        bool $required = true,
    ): Select {
        return Select::make($name)
            ->relationship($relationship, $titleAttribute)
            ->searchable()
            ->preload()
            ->required($required)
            ->helperText(function () use ($modelClass, $emptyHelperText, $foundHelperText): string {
                return $modelClass::query()->exists() ? $foundHelperText : $emptyHelperText;
            })
            ->hintAction(
                Action::make('create_related_'.$name)
                    ->label($createLabel)
                    ->icon('heroicon-m-plus')
                    ->url($createUrl)
                    ->openUrlInNewTab(),
            );
    }
}
