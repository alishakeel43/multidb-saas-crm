<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ma_workflows', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('entity_type', 32);
            $table->string('event');
            $table->string('condition_type', 8)->default('and');
            $table->json('conditions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['event', 'is_active'], 'ma_workflows_event_active_idx');
        });

        Schema::create('ma_workflow_actions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('workflow_id');
            $table->string('branch', 32);
            $table->unsignedInteger('sort')->default(0);
            $table->string('type', 32);
            $table->json('config')->nullable();
            $table->timestamps();

            $table->foreign('workflow_id')->references('id')->on('ma_workflows')->cascadeOnDelete();
            $table->index(['workflow_id', 'branch', 'sort'], 'ma_workflow_actions_order_idx');
        });

        Schema::create('ma_workflow_runs', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('workflow_id');
            $table->string('branch', 32);
            $table->unsignedInteger('next_action_index')->default(0);
            $table->json('context');
            $table->string('status', 32)->default('pending');
            $table->timestamp('run_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->foreign('workflow_id')->references('id')->on('ma_workflows')->cascadeOnDelete();
            $table->index(['status', 'run_at'], 'ma_workflow_runs_due_idx');
        });

        Schema::create('ma_audiences', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->json('rules')->nullable();
            $table->timestamps();
        });

        Schema::create('ma_campaigns', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('status', 32)->default('draft');
            $table->decimal('budget', 12, 2)->default(0);
            $table->decimal('spent', 12, 2)->default(0);
            $table->string('currency', 8)->default('USD');
            $table->unsignedInteger('audience_id')->nullable();
            $table->string('source_name')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();

            $table->foreign('audience_id')->references('id')->on('ma_audiences')->nullOnDelete();
            $table->index('utm_campaign', 'ma_campaigns_utm_idx');
            $table->index('status', 'ma_campaigns_status_idx');
        });

        Schema::create('ma_email_sequences', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('campaign_id')->nullable();
            $table->unsignedInteger('audience_id')->nullable();
            $table->string('name');
            $table->string('status', 32)->default('draft');
            $table->timestamps();

            $table->foreign('campaign_id')->references('id')->on('ma_campaigns')->nullOnDelete();
            $table->foreign('audience_id')->references('id')->on('ma_audiences')->nullOnDelete();
        });

        Schema::create('ma_email_steps', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('sequence_id');
            $table->unsignedInteger('sort')->default(0);
            $table->unsignedInteger('delay_minutes')->default(0);
            $table->string('subject');
            $table->text('body');
            $table->string('link_url')->nullable();
            $table->string('link_label')->nullable();
            $table->timestamps();

            $table->foreign('sequence_id')->references('id')->on('ma_email_sequences')->cascadeOnDelete();
            $table->index(['sequence_id', 'sort'], 'ma_email_steps_order_idx');
        });

        Schema::create('ma_email_enrollments', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('sequence_id');
            $table->unsignedInteger('person_id');
            $table->string('status', 32)->default('active');
            $table->unsignedInteger('current_step')->default(0);
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('next_run_at')->nullable();
            $table->string('unsubscribe_token', 64)->unique();
            $table->timestamps();

            $table->foreign('sequence_id')->references('id')->on('ma_email_sequences')->cascadeOnDelete();
            $table->index(['status', 'next_run_at'], 'ma_email_enrollments_due_idx');
            $table->index(['sequence_id', 'person_id'], 'ma_email_enrollments_person_idx');
        });

        Schema::create('ma_email_messages', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('enrollment_id')->nullable();
            $table->unsignedInteger('step_id')->nullable();
            $table->unsignedInteger('person_id')->nullable();
            $table->unsignedInteger('lead_id')->nullable();
            $table->string('email');
            $table->string('subject');
            $table->string('link_url')->nullable();
            $table->string('token', 64)->unique();
            $table->string('status', 32)->default('queued');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('clicked_at')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->foreign('enrollment_id')->references('id')->on('ma_email_enrollments')->nullOnDelete();
            $table->foreign('step_id')->references('id')->on('ma_email_steps')->nullOnDelete();
            $table->index('status', 'ma_email_messages_status_idx');
            $table->index('lead_id', 'ma_email_messages_lead_idx');
        });

        Schema::create('ma_attributions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('lead_id')->nullable();
            $table->unsignedInteger('person_id')->nullable();
            $table->unsignedInteger('campaign_id')->nullable();
            $table->unsignedInteger('web_form_id')->nullable();
            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->string('utm_content')->nullable();
            $table->string('utm_term')->nullable();
            $table->text('referrer')->nullable();
            $table->text('landing_url')->nullable();
            $table->timestamps();

            $table->foreign('campaign_id')->references('id')->on('ma_campaigns')->nullOnDelete();
            $table->index('lead_id', 'ma_attributions_lead_idx');
            $table->index('utm_campaign', 'ma_attributions_utm_idx');
            $table->index('campaign_id', 'ma_attributions_campaign_idx');
        });

        Schema::create('ma_engagement_rules', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('event_type', 64);
            $table->integer('points')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['event_type', 'is_active'], 'ma_engagement_rules_event_idx');
        });

        Schema::create('ma_engagement_scores', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('lead_id')->unique();
            $table->integer('points')->default(0);
            $table->timestamps();
        });

        Schema::create('ma_engagement_events', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('lead_id');
            $table->string('event_type', 64);
            $table->string('source_key');
            $table->integer('points')->default(0);
            $table->timestamps();

            $table->unique(['lead_id', 'event_type', 'source_key'], 'ma_engagement_events_once_idx');
            $table->index('lead_id', 'ma_engagement_events_lead_idx');
        });

        $now = now();

        DB::table('ma_engagement_rules')->insert([
            ['name' => 'Email opened', 'event_type' => 'email_open', 'points' => 5, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Email clicked', 'event_type' => 'email_click', 'points' => 10, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Form submitted', 'event_type' => 'form_submit', 'points' => 15, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Unsubscribed', 'event_type' => 'unsubscribe', 'points' => -20, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('ma_engagement_events');
        Schema::dropIfExists('ma_engagement_scores');
        Schema::dropIfExists('ma_engagement_rules');
        Schema::dropIfExists('ma_attributions');
        Schema::dropIfExists('ma_email_messages');
        Schema::dropIfExists('ma_email_enrollments');
        Schema::dropIfExists('ma_email_steps');
        Schema::dropIfExists('ma_email_sequences');
        Schema::dropIfExists('ma_campaigns');
        Schema::dropIfExists('ma_audiences');
        Schema::dropIfExists('ma_workflow_runs');
        Schema::dropIfExists('ma_workflow_actions');
        Schema::dropIfExists('ma_workflows');
    }
};
