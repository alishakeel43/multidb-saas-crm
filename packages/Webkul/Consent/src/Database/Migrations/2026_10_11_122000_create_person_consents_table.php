<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('person_consents', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('person_id');
            $table->string('channel'); // email|sms|phone|whatsapp|marketing
            $table->boolean('is_opted_in')->default(false);
            $table->string('source')->nullable();
            $table->unsignedInteger('changed_by')->nullable();
            $table->timestamp('captured_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // ConsentService::set / isOptedIn / canMarket
            $table->unique(['person_id', 'channel'], 'person_consents_person_channel_unique');

            // suppression / marketing list by channel
            $table->index(['channel', 'is_opted_in'], 'person_consents_channel_opt_in_idx');

            // admin recent activity listing
            $table->index('updated_at', 'person_consents_updated_at_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('person_consents');
    }
};
