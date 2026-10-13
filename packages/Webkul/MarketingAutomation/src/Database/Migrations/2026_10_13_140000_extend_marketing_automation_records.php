<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ma_campaigns') && ! Schema::hasColumn('ma_campaigns', 'marketing_campaign_id')) {
            Schema::table('ma_campaigns', function (Blueprint $table) {
                $table->unsignedInteger('marketing_campaign_id')->nullable()->unique();
            });
        }

        if (! Schema::hasTable('ma_core_workflow_runs')) {
            Schema::create('ma_core_workflow_runs', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('workflow_id')->default(0);
                $table->string('entity_type', 32);
                $table->unsignedInteger('entity_id');
                $table->json('actions');
                $table->string('status', 32)->default('pending');
                $table->timestamp('run_at')->nullable();
                $table->timestamp('finished_at')->nullable();
                $table->text('last_error')->nullable();
                $table->timestamps();

                $table->index(['status', 'run_at'], 'ma_core_workflow_runs_due_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ma_core_workflow_runs');

        if (Schema::hasTable('ma_campaigns') && Schema::hasColumn('ma_campaigns', 'marketing_campaign_id')) {
            Schema::table('ma_campaigns', function (Blueprint $table) {
                $table->dropUnique(['marketing_campaign_id']);
                $table->dropColumn('marketing_campaign_id');
            });
        }
    }
};
