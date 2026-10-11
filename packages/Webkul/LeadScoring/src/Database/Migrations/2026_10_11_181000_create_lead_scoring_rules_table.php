<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_scoring_rules', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('field'); // title|lead_value|lead_source_id|lead_type_id|status
            $table->string('operator', 32); // eq|neq|gt|gte|lt|lte|contains
            $table->string('value')->nullable();
            $table->integer('points')->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('priority')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'priority'], 'lead_scoring_rules_active_priority_idx');
        });

        if (Schema::hasTable('leads') && ! Schema::hasColumn('leads', 'score')) {
            Schema::table('leads', function (Blueprint $table) {
                $table->integer('score')->default(0)->after('lead_value');
                $table->index('score', 'leads_score_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('leads') && Schema::hasColumn('leads', 'score')) {
            Schema::table('leads', function (Blueprint $table) {
                $table->dropIndex('leads_score_idx');
                $table->dropColumn('score');
            });
        }

        Schema::dropIfExists('lead_scoring_rules');
    }
};
