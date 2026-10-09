<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status', 30)->default('pending');
            $table->string('priority', 30)->default('medium');
            $table->date('due_date')->nullable();
            $table->time('due_time')->nullable();

            $table->unsignedInteger('assigned_to')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('completed_by')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->string('entity_type', 60)->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();

            $table->timestamps();

            $table->foreign('assigned_to')->references('id')->on('users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('completed_by')->references('id')->on('users')->onDelete('set null');

            $table->index('status');
            $table->index('priority');
            $table->index('assigned_to');
            $table->index('created_by');
            $table->index('due_date');
            $table->index('completed_at');
            $table->index(['assigned_to', 'status'], 'tasks_assigned_status_idx');
            $table->index(['status', 'due_date'], 'tasks_status_due_idx');
            $table->index(['assigned_to', 'due_date'], 'tasks_assigned_due_idx');
            $table->index(['entity_type', 'entity_id'], 'tasks_entity_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
