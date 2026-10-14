<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_stages', function (Blueprint $table) {
            $table->increments('id');
            $table->string('code', 50);
            $table->string('name', 100);
            $table->string('color', 20)->default('#2563EB');
            $table->unsignedInteger('sort_order')->default(1);
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->unique('code', 'task_stages_code_unique');
            $table->index('sort_order', 'task_stages_sort_order_idx');
        });

        Schema::create('tasks', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status', 50)->default('pending');
            $table->string('priority', 30)->default('medium');
            $table->date('due_date')->nullable();
            $table->time('due_time')->nullable();
            $table->unsignedInteger('assigned_to')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('completed_by')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('entity_type', 60)->nullable();
            $table->unsignedInteger('entity_id')->nullable();
            $table->timestamp('reminder_sent_at')->nullable();
            $table->timestamps();

            $table->foreign('assigned_to')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('completed_by')->references('id')->on('users')->nullOnDelete();

            $table->index(['status', 'due_date'], 'tasks_status_due_idx');
            $table->index(['assigned_to', 'status'], 'tasks_assigned_status_idx');
            $table->index(['entity_type', 'entity_id'], 'tasks_entity_idx');
            $table->index('priority', 'tasks_priority_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
        Schema::dropIfExists('task_stages');
    }
};
