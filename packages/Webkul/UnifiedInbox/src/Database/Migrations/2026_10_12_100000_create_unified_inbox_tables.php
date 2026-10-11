<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unified_inbox_connections', function (Blueprint $table) {
            $table->increments('id');
            $table->string('channel', 40);
            $table->string('external_id', 100);
            $table->string('name');
            $table->string('status', 30)->default('pending');
            $table->text('credentials')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->unique(['channel', 'external_id'], 'ui_connection_identity');
            $table->index(['channel', 'status'], 'ui_connection_channel_status_idx');
        });

        Schema::create('unified_inbox_conversations', function (Blueprint $table) {
            $table->increments('id');
            $table->string('channel', 40);
            $table->string('account_id', 100);
            $table->string('external_id', 191);
            $table->string('customer_name');
            $table->unsignedInteger('user_id')->nullable();
            $table->string('status', 20)->default('open');
            $table->unsignedInteger('unread_count')->default(0);
            $table->text('last_message_preview')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamp('last_incoming_at')->nullable();
            $table->unsignedInteger('person_id')->nullable();
            $table->unsignedInteger('lead_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['channel', 'account_id', 'external_id'], 'ui_conversation_identity');
            $table->index(['status', 'last_message_at'], 'ui_conversation_status_idx');
            $table->index('channel', 'ui_conversation_channel_idx');
        });

        Schema::create('unified_inbox_messages', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('conversation_id');
            $table->string('direction', 10);
            $table->string('external_id', 191)->nullable();
            $table->text('body')->nullable();
            $table->string('status', 20);
            $table->json('attachments')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->foreign('conversation_id')
                ->references('id')
                ->on('unified_inbox_conversations')
                ->cascadeOnDelete();
            $table->unique(['conversation_id', 'external_id'], 'ui_message_external');
            $table->index(['conversation_id', 'id'], 'ui_history');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unified_inbox_messages');
        Schema::dropIfExists('unified_inbox_conversations');
        Schema::dropIfExists('unified_inbox_connections');
    }
};
