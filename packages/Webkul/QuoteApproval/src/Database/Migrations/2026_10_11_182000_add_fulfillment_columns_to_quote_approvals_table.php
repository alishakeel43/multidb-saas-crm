<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('quote_approvals')) {
            return;
        }

        Schema::table('quote_approvals', function (Blueprint $table) {
            if (! Schema::hasColumn('quote_approvals', 'delivery_status')) {
                $table->string('delivery_status', 32)->default('none')->after('notes');
            }
            if (! Schema::hasColumn('quote_approvals', 'order_reference')) {
                $table->string('order_reference')->nullable()->after('delivery_status');
            }
            if (! Schema::hasColumn('quote_approvals', 'converted_at')) {
                $table->timestamp('converted_at')->nullable()->after('order_reference');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('quote_approvals')) {
            return;
        }

        Schema::table('quote_approvals', function (Blueprint $table) {
            foreach (['converted_at', 'order_reference', 'delivery_status'] as $column) {
                if (Schema::hasColumn('quote_approvals', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
