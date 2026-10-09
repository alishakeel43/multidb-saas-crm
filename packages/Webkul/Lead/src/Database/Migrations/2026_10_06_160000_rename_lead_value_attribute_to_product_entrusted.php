<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Existing workspaces store the lead amount label on the attribute row.
     * The amount stays a price so currency totals keep using the same column.
     */
    public function up(): void
    {
        DB::table('attributes')
            ->where('code', 'lead_value')
            ->where('entity_type', 'leads')
            ->update([
                'name' => 'Product Entrusted',
                'type' => 'price',
                'validation' => 'decimal',
                'updated_at' => now(),
            ]);
    }

    /**
     * Restore the previous lead amount label.
     */
    public function down(): void
    {
        DB::table('attributes')
            ->where('code', 'lead_value')
            ->where('entity_type', 'leads')
            ->where('name', 'Product Entrusted')
            ->update([
                'name' => 'Estimated Lead Value',
                'type' => 'price',
                'validation' => 'decimal',
                'updated_at' => now(),
            ]);
    }
};
