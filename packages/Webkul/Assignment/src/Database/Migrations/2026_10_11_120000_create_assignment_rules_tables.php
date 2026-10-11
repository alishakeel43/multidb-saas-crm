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

            $table->unique('assignment_rule_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignment_cursors');
        Schema::dropIfExists('assignment_rule_users');
        Schema::dropIfExists('assignment_rules');
    }
};
