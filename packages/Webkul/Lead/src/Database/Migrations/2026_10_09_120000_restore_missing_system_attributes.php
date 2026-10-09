<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Restore required system attribute metadata without replacing tenant settings.
     */
    public function up(): void
    {
        $now = now();
        $locale = config('app.locale');

        $attributes = [
            ['title', trans('installer::app.seeders.attributes.leads.title', [], $locale), 'text', null, 'leads', 1, 1, 1, 0],
            ['description', trans('installer::app.seeders.attributes.leads.description', [], $locale), 'textarea', null, 'leads', 2, 0, 1, 0],
            ['lead_value', 'Product Entrusted', 'text', null, 'leads', 3, 0, 1, 0],
            ['lead_source_id', trans('installer::app.seeders.attributes.leads.source', [], $locale), 'select', 'lead_sources', 'leads', 4, 1, 0, 0],
            ['lead_type_id', trans('installer::app.seeders.attributes.leads.type', [], $locale), 'select', 'lead_types', 'leads', 5, 1, 0, 0],
            ['user_id', trans('installer::app.seeders.attributes.leads.sales-owner', [], $locale), 'select', 'users', 'leads', 7, 0, 0, 0],
            ['expected_close_date', trans('installer::app.seeders.attributes.leads.expected-close-date', [], $locale), 'date', null, 'leads', 8, 0, 0, 0],
            ['organization_id', trans('installer::app.seeders.attributes.persons.organization', [], $locale), 'lookup', 'organizations', 'persons', 6, 0, 0, 0],
        ];

        foreach ($attributes as [$code, $name, $type, $lookupType, $entityType, $sortOrder, $required, $quickAdd, $unique]) {
            DB::table('attributes')->insertOrIgnore([
                'code' => $code,
                'name' => $name,
                'type' => $type,
                'lookup_type' => $lookupType,
                'entity_type' => $entityType,
                'sort_order' => $sortOrder,
                'validation' => null,
                'is_required' => $required,
                'is_unique' => $unique,
                'quick_add' => $quickAdd,
                'is_user_defined' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /**
     * Keep restored metadata on rollback because tenant records may now depend on it.
     */
    public function down(): void
    {
        //
    }
};
