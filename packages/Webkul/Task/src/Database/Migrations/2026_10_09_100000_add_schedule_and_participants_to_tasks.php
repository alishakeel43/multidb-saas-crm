<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dateTime('schedule_from')->nullable();
            $table->dateTime('schedule_to')->nullable();
            $table->string('location')->nullable();
            $table->unsignedSmallInteger('reminder_minutes')->nullable();
        });

        Schema::create('task_participants', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('task_id');
            $table->unsignedInteger('user_id')->nullable();
            $table->unsignedInteger('person_id')->nullable();
            $table->timestamps();

            $table->foreign('task_id')->references('id')->on('tasks')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('person_id')->references('id')->on('persons')->cascadeOnDelete();
            $table->unique(['task_id', 'user_id']);
            $table->unique(['task_id', 'person_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_participants');

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn(['schedule_from', 'schedule_to', 'location', 'reminder_minutes']);
        });
    }
};
