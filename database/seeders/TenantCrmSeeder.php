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
    private const BASELINES = [
        [
            'tables' => ['countries', 'country_states'],
            'seeder' => CoreSeeder::class,
        ],
        [
            'tables' => ['attributes'],
            'seeder' => AttributeSeeder::class,
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
            if ($this->tablesAreEmpty($baseline['tables'])) {
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
