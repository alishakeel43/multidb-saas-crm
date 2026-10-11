<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bookings')) {
            return;
        }

        Schema::table('bookings', function (Blueprint $table) {
            if (! Schema::hasColumn('bookings', 'reminder_sent_at')) {
                $table->timestamp('reminder_sent_at')->nullable()->after('notes');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('bookings') || ! Schema::hasColumn('bookings', 'reminder_sent_at')) {
            return;
        }

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('reminder_sent_at');
        });
    }
};
