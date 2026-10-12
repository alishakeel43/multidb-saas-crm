<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('call_logs', function (Blueprint $table) {
            $table->id();
            $table->string('provider_call_id', 191)->unique();
            $table->enum('direction', ['inbound', 'outbound']);
            $table->string('status', 50);
            $table->string('from_number', 30)->nullable();
            $table->string('to_number', 30)->nullable();
            $table->unsignedInteger('contact_id')->nullable();
            $table->unsignedInteger('lead_id')->nullable();
            $table->unsignedInteger('user_id')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->text('recording_url')->nullable();
            $table->string('recording_sid', 191)->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->json('provider_payload')->nullable();
            $table->timestamps();

            $table->foreign('contact_id')->references('id')->on('persons')->nullOnDelete();
            $table->foreign('lead_id')->references('id')->on('leads')->nullOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();

            $table->index('contact_id');
            $table->index('lead_id');
            $table->index('user_id');
            $table->index('direction');
            $table->index('status');
            $table->index('started_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('call_logs');
    }
};
