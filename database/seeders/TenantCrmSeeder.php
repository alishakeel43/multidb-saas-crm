<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Webkul\Installer\Database\Seeders\Attribute\DatabaseSeeder as AttributeSeeder;
use Webkul\Installer\Database\Seeders\Core\DatabaseSeeder as CoreSeeder;
use Webkul\Installer\Database\Seeders\EmailTemplate\DatabaseSeeder as EmailTemplateSeeder;
use Webkul\Installer\Database\Seeders\Lead\DatabaseSeeder as LeadSeeder;
use Webkul\Installer\Database\Seeders\Workflow\DatabaseSeeder as WorkflowSeeder;

class TenantCrmSeeder extends Seeder
{
    /**
     * System attributes that must exist after a healthy CRM baseline.
     * Migrations may insert a few rows first (job_title, user_id, warehouses…),
     * so an “attributes table not empty” check is not enough.
     *
     * @var list<array{0: string, 1: string}>
     */
    private const REQUIRED_ATTRIBUTES = [
        ['leads', 'title'],
        ['persons', 'name'],
        ['persons', 'emails'],
        ['persons', 'organization_id'],
        ['organizations', 'name'],
        ['products', 'name'],
        ['quotes', 'name'],
    ];

    private const BASELINES = [
        [
            'tables' => ['countries', 'country_states'],
            'seeder' => CoreSeeder::class,
        ],
        [
            'tables' => ['attributes'],
            'seeder' => AttributeSeeder::class,
            'required_attributes' => true,
        ],
        [
            'tables' => ['email_templates'],
            'seeder' => EmailTemplateSeeder::class,
        ],
        [
            'tables' => ['lead_pipelines', 'lead_pipeline_stages', 'lead_types', 'lead_sources'],
            'seeder' => LeadSeeder::class,
        ],
        [
            'tables' => ['workflows'],
            'seeder' => WorkflowSeeder::class,
        ],
    ];

    public function run(): void
    {
        DB::setDefaultConnection('tenant');

        foreach (self::BASELINES as $baseline) {
            if ($this->shouldSeedBaseline($baseline)) {
                $this->call($baseline['seeder']);
            }
        }

        $roles = DB::connection('tenant')->table('roles');

        if (! $roles->where('name', 'Administrator')->where('permission_type', 'all')->exists()) {
            $roles->insert([
                'name' => 'Administrator',
                'description' => 'Tenant administrator',
                'permission_type' => 'all',
                'permissions' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * @param  array{tables: list<string>, seeder: class-string, required_attributes?: bool}  $baseline
     */
    private function shouldSeedBaseline(array $baseline): bool
    {
        if (! empty($baseline['required_attributes'])) {
            return $this->attributesBaselineIncomplete();
        }

        return $this->tablesAreEmpty($baseline['tables']);
    }

    private function attributesBaselineIncomplete(): bool
    {
        if (! Schema::connection('tenant')->hasTable('attributes')) {
            throw new \RuntimeException('Tenant baseline table [attributes] does not exist; run tenant migrations first.');
        }

        $connection = DB::connection('tenant');

        foreach (self::REQUIRED_ATTRIBUTES as [$entityType, $code]) {
            $exists = $connection->table('attributes')
                ->where('entity_type', $entityType)
                ->where('code', $code)
                ->exists();

            if (! $exists) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $tables
     */
    private function tablesAreEmpty(array $tables): bool
    {
        foreach ($tables as $table) {
            if (! Schema::connection('tenant')->hasTable($table)) {
                throw new \RuntimeException("Tenant baseline table [{$table}] does not exist; run tenant migrations first.");
            }

            if (DB::connection('tenant')->table($table)->exists()) {
                return false;
            }
        }

        return true;
    }
}
