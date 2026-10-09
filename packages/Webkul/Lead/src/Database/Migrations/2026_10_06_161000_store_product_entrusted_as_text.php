<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Product Entrusted is a name, so Quick Add and the lead column store text.
     */
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->string('lead_value', 255)->nullable()->change();
        });

        DB::table('attributes')
            ->where('code', 'lead_value')
            ->where('entity_type', 'leads')
            ->update([
                'type' => 'text',
                'validation' => null,
                'quick_add' => 1,
                'updated_at' => now(),
            ]);

        DB::table('leads')
            ->whereNotNull('lead_value')
            ->orderBy('id')
            ->chunkById(500, function ($leads): void {
                foreach ($leads as $lead) {
                    $value = (string) $lead->lead_value;

                    if (preg_match('/^[0-9]+\\.[0-9]+$/D', $value) !== 1) {
                        continue;
                    }

                    $normalizedValue = rtrim(rtrim($value, '0'), '.');

                    DB::table('leads')
                        ->where('id', $lead->id)
                        ->update(['lead_value' => $normalizedValue === '' ? '0' : $normalizedValue]);
                }
            });
    }

    /**
     * Restore the previous numeric lead amount.
     */
    public function down(): void
    {
        DB::table('attributes')
            ->where('code', 'lead_value')
            ->where('entity_type', 'leads')
            ->update([
                'type' => 'price',
                'validation' => 'decimal',
                'quick_add' => 0,
                'updated_at' => now(),
            ]);

        Schema::table('leads', function (Blueprint $table) {
            $table->decimal('lead_value', 12, 4)->nullable()->change();
        });
    }
};
