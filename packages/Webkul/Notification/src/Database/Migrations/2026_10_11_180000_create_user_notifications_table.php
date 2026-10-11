<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_notifications', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->string('type', 64);
            $table->string('title');
            $table->text('body')->nullable();
            $table->string('link')->nullable();
            $table->string('entity_type', 64)->nullable();
            $table->unsignedInteger('entity_id')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'read_at', 'created_at'], 'user_notifications_inbox_idx');
            $table->index(['type', 'created_at'], 'user_notifications_type_idx');
            $table->index(['user_id', 'type', 'entity_type', 'entity_id'], 'user_notifications_dedupe_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_notifications');
    }
};
