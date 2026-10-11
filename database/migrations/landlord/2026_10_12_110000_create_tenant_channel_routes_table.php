<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('landlord')->create('tenant_channel_routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('channel', 40); // whatsapp, messenger, instagram
            $table->string('external_id', 100); // phone_number_id, page_id, instagram_id
            $table->string('waba_id', 100)->nullable();
            $table->string('account_name', 191)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['channel', 'external_id'], 'landlord_chan_ext_unique');
            $table->index(['external_id', 'channel'], 'landlord_ext_chan_idx');
            $table->index('waba_id', 'landlord_waba_idx');
        });
    }

    public function down(): void
    {
        Schema::connection('landlord')->dropIfExists('tenant_channel_routes');
    }
};
