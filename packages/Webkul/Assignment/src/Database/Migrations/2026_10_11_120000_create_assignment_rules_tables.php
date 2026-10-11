<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assignment_rules', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('entity_type')->default('leads');
            $table->string('strategy'); // round_robin|least_workload|first_available
            $table->boolean('is_active')->default(true);
            $table->boolean('only_when_empty')->default(true);
            $table->unsignedInteger('priority')->default(0);
            $table->json('conditions')->nullable();
            $table->timestamps();

            // resolveRule(): active rules by entity, highest priority first
            $table->index(
                ['entity_type', 'is_active', 'priority'],
                'assignment_rules_entity_active_priority_idx'
            );
        });

        Schema::create('assignment_rule_users', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('assignment_rule_id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('assignment_rule_id')
                ->references('id')
                ->on('assignment_rules')
                ->onDelete('cascade');

            $table->unique(['assignment_rule_id', 'user_id'], 'assignment_rule_user_unique');

            // activeCandidates(): ordered pool for a rule
            $table->index(
                ['assignment_rule_id', 'is_active', 'sort_order'],
                'assignment_rule_users_rule_active_sort_idx'
            );

            // reverse lookup: which rules include a user
            $table->index('user_id', 'assignment_rule_users_user_id_idx');
        });

        Schema::create('assignment_cursors', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('assignment_rule_id');
            $table->unsignedInteger('last_user_id')->nullable();
            $table->timestamps();

            $table->foreign('assignment_rule_id')
                ->references('id')
                ->on('assignment_rules')
                ->onDelete('cascade');

            // round-robin cursor lockForUpdate() by rule
            $table->unique('assignment_rule_id', 'assignment_cursors_rule_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignment_cursors');
        Schema::dropIfExists('assignment_rule_users');
        Schema::dropIfExists('assignment_rules');
    }
};
