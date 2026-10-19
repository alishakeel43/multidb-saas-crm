<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Additive only (tenant DB): Meta-style folders (follow up / spam), labels, avatar and saved quick replies.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('unified_inbox_conversations', function (Blueprint $table) {
            if (! Schema::hasColumn('unified_inbox_conversations', 'is_follow_up')) {
                $table->boolean('is_follow_up')->nullable();
            }
            if (! Schema::hasColumn('unified_inbox_conversations', 'is_spam')) {
                $table->boolean('is_spam')->nullable();
            }
            if (! Schema::hasColumn('unified_inbox_conversations', 'labels')) {
                $table->json('labels')->nullable();
            }
            if (! Schema::hasColumn('unified_inbox_conversations', 'avatar_url')) {
                $table->string('avatar_url', 1024)->nullable();
            }
        });

        if (! Schema::hasTable('unified_inbox_quick_replies')) {
            Schema::create('unified_inbox_quick_replies', function (Blueprint $table) {
                $table->increments('id');
                $table->string('title', 120);
                $table->string('shortcut', 60)->nullable();
                $table->text('body');
                $table->unsignedInteger('user_id')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();

                $table->index('shortcut', 'ui_quick_reply_shortcut_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('unified_inbox_quick_replies');

        foreach (['is_follow_up', 'is_spam', 'labels', 'avatar_url'] as $column) {
            if (Schema::hasColumn('unified_inbox_conversations', $column)) {
                Schema::table('unified_inbox_conversations', fn (Blueprint $table) => $table->dropColumn($column));
            }
        }
    }
};
