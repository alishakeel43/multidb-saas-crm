<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('unified_inbox_conversations', 'organization_id')) {
            Schema::table('unified_inbox_conversations', function (Blueprint $table) {
                $table->unsignedInteger('organization_id')->nullable()->after('lead_id');
                $table->index('organization_id', 'ui_conversation_org_idx');
            });
        }

        // Ties Krayin activities (internal notes) to the conversation they were written in.
        if (! Schema::hasTable('unified_inbox_conversation_activities')) {
            Schema::create('unified_inbox_conversation_activities', function (Blueprint $table) {
                $table->unsignedInteger('conversation_id');
                $table->unsignedInteger('activity_id');
                $table->primary(['conversation_id', 'activity_id'], 'ui_conv_activity_pk');
                $table->foreign('conversation_id', 'ui_conv_activity_conv_fk')->references('id')->on('unified_inbox_conversations')->cascadeOnDelete();
                $table->foreign('activity_id', 'ui_conv_activity_act_fk')->references('id')->on('activities')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('unified_inbox_conversation_activities');

        if (Schema::hasColumn('unified_inbox_conversations', 'organization_id')) {
            Schema::table('unified_inbox_conversations', function (Blueprint $table) {
                $table->dropIndex('ui_conversation_org_idx');
                $table->dropColumn('organization_id');
            });
        }
    }
};
