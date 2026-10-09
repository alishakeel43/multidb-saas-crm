<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            if (! Schema::hasColumn('activities', 'reminder_minutes')) {
                $table->unsignedSmallInteger('reminder_minutes')->nullable();
            }
        });

        Schema::create('activity_reminders', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('activity_id')->nullable();
            $table->foreign('activity_id')->references('id')->on('activities')->nullOnDelete();
            $table->unsignedInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->dateTime('remind_at');
            $table->string('channel', 16);
            $table->string('status', 16)->default('pending');
            $table->uuid('idempotency_key')->unique();
            $table->timestamp('sent_at')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();
            $table->index(['status', 'remind_at'], 'reminders_due_index');
            $table->index(['activity_id', 'status'], 'reminders_activity_index');
        });

        if (! Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('type');
                $table->morphs('notifiable');
                $table->text('data');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_reminders');

        Schema::table('activities', function (Blueprint $table) {
            if (Schema::hasColumn('activities', 'reminder_minutes')) {
                $table->dropColumn('reminder_minutes');
            }
        });
    }
};
