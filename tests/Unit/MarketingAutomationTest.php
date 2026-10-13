<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;
use Webkul\Lead\Models\Lead;
use Webkul\MarketingAutomation\Mail\SequenceMail;
use Webkul\MarketingAutomation\Models\Audience;
use Webkul\MarketingAutomation\Models\Campaign;
use Webkul\MarketingAutomation\Models\CoreWorkflowRun;
use Webkul\MarketingAutomation\Models\EmailEnrollment;
use Webkul\MarketingAutomation\Models\EmailMessage;
use Webkul\MarketingAutomation\Models\EmailSequence;
use Webkul\MarketingAutomation\Models\EmailStep;
use Webkul\MarketingAutomation\Models\EngagementScore;
use Webkul\MarketingAutomation\Models\Workflow;
use Webkul\MarketingAutomation\Models\WorkflowAction;
use Webkul\MarketingAutomation\Models\WorkflowRun;
use Webkul\MarketingAutomation\Services\AttributionRecorder;
use Webkul\MarketingAutomation\Services\CampaignReport;
use Webkul\MarketingAutomation\Services\CoreWorkflowActions;
use Webkul\MarketingAutomation\Services\EmailDispatcher;
use Webkul\MarketingAutomation\Services\SequenceRunner;
use Webkul\MarketingAutomation\Services\TrackingService;
use Webkul\MarketingAutomation\Services\WorkflowEngine;

uses(TestCase::class);

beforeEach(function () {
    config([
        'database.default' => 'sqlite',
        'database.connections.sqlite' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ],
    ]);

    DB::purge('sqlite');
    DB::reconnect('sqlite');
    URL::forceRootUrl('http://localhost');

    $migration = require base_path('packages/Webkul/MarketingAutomation/src/Database/Migrations/2026_10_13_130000_create_marketing_automation_tables.php');
    $migration->up();

    $extension = require base_path('packages/Webkul/MarketingAutomation/src/Database/Migrations/2026_10_13_140000_extend_marketing_automation_records.php');
    $extension->up();

    Schema::create('users', function (Blueprint $table) {
        $table->increments('id');
        $table->string('name')->nullable();
        $table->timestamps();
    });

    Schema::create('lead_sources', function (Blueprint $table) {
        $table->increments('id');
        $table->string('name');
    });

    Schema::create('leads', function (Blueprint $table) {
        $table->increments('id');
        $table->string('title')->nullable();
        $table->text('description')->nullable();
        $table->decimal('lead_value', 12, 2)->default(0);
        $table->integer('status')->default(1);
        $table->unsignedInteger('user_id')->nullable();
        $table->unsignedInteger('person_id')->nullable();
        $table->unsignedInteger('lead_source_id')->nullable();
        $table->integer('score')->default(0);
        $table->timestamps();
    });

    Schema::create('persons', function (Blueprint $table) {
        $table->increments('id');
        $table->string('name')->nullable();
        $table->string('job_title')->nullable();
        $table->json('emails')->nullable();
        $table->unsignedInteger('user_id')->nullable();
        $table->timestamps();
    });

    Schema::create('activities', function (Blueprint $table) {
        $table->increments('id');
        $table->string('title')->nullable();
        $table->string('type');
        $table->text('comment')->nullable();
        $table->json('additional')->nullable();
        $table->dateTime('schedule_from')->nullable();
        $table->dateTime('schedule_to')->nullable();
        $table->boolean('is_done')->default(false);
        $table->unsignedInteger('user_id')->nullable();
        $table->timestamps();
    });

    Schema::create('lead_activities', function (Blueprint $table) {
        $table->unsignedInteger('activity_id');
        $table->unsignedInteger('lead_id');
    });

    Schema::create('person_activities', function (Blueprint $table) {
        $table->unsignedInteger('activity_id');
        $table->unsignedInteger('person_id');
    });

    Schema::create('person_consents', function (Blueprint $table) {
        $table->increments('id');
        $table->unsignedInteger('person_id');
        $table->string('channel');
        $table->boolean('is_opted_in')->default(false);
        $table->string('source')->nullable();
        $table->unsignedInteger('changed_by')->nullable();
        $table->timestamp('captured_at')->nullable();
        $table->text('notes')->nullable();
        $table->timestamps();
        $table->unique(['person_id', 'channel']);
    });

    Schema::create('user_notifications', function (Blueprint $table) {
        $table->increments('id');
        $table->unsignedInteger('user_id');
        $table->string('type', 64);
        $table->string('title');
        $table->text('body')->nullable();
        $table->string('link')->nullable();
        $table->string('entity_type', 64)->nullable();
        $table->unsignedInteger('entity_id')->nullable();
        $table->json('meta')->nullable();
        $table->timestamp('read_at')->nullable();
        $table->timestamps();
    });

    DB::table('users')->insert(['id' => 1, 'name' => 'Owner', 'created_at' => now(), 'updated_at' => now()]);
});

function marketingLead(array $overrides = []): Lead
{
    $id = DB::table('leads')->insertGetId(array_merge([
        'title' => 'Enterprise deal',
        'lead_value' => 2500,
        'status' => 1,
        'user_id' => 1,
        'person_id' => null,
        'score' => 40,
        'created_at' => now(),
        'updated_at' => now(),
    ], $overrides));

    return Lead::query()->findOrFail($id);
}

it('runs the matching branch immediately and the other branch as a notification', function () {
    $workflow = Workflow::query()->create([
        'name' => 'High value',
        'entity_type' => 'leads',
        'event' => 'lead.create.after',
        'condition_type' => 'and',
        'conditions' => [['attribute' => 'lead_value', 'operator' => 'gte', 'value' => '1000']],
        'is_active' => true,
    ]);

    WorkflowAction::query()->create([
        'workflow_id' => $workflow->id,
        'branch' => 'when_match',
        'sort' => 0,
        'type' => 'task',
        'config' => ['title' => 'Call the buyer'],
    ]);

    WorkflowAction::query()->create([
        'workflow_id' => $workflow->id,
        'branch' => 'when_else',
        'sort' => 0,
        'type' => 'notification',
        'config' => ['title' => 'Small lead'],
    ]);

    $engine = app(WorkflowEngine::class);
    $engine->handle('lead.create.after', marketingLead(['lead_value' => 50, 'title' => 'Small']));

    expect(DB::table('activities')->count())->toBe(0)
        ->and(DB::table('user_notifications')->where('title', 'Small lead')->count())->toBe(1);

    $engine->handle('lead.create.after', marketingLead());

    expect(DB::table('activities')->where('title', 'Call the buyer')->count())->toBe(1)
        ->and(DB::table('lead_activities')->count())->toBe(1);
});

it('waits out a delay before creating the task and can update the lead', function () {
    $workflow = Workflow::query()->create([
        'name' => 'Delayed update',
        'entity_type' => 'leads',
        'event' => 'lead.update.after',
        'condition_type' => 'and',
        'conditions' => [],
        'is_active' => true,
    ]);

    WorkflowAction::query()->create([
        'workflow_id' => $workflow->id,
        'branch' => 'when_match',
        'sort' => 0,
        'type' => 'delay',
        'config' => ['minutes' => 30],
    ]);

    WorkflowAction::query()->create([
        'workflow_id' => $workflow->id,
        'branch' => 'when_match',
        'sort' => 1,
        'type' => 'update',
        'config' => ['field' => 'title', 'value' => 'Updated later'],
    ]);

    WorkflowAction::query()->create([
        'workflow_id' => $workflow->id,
        'branch' => 'when_match',
        'sort' => 2,
        'type' => 'task',
        'config' => ['title' => 'After delay'],
    ]);

    $lead = marketingLead();
    app(WorkflowEngine::class)->handle('lead.update.after', $lead);

    expect(DB::table('activities')->count())->toBe(0)
        ->and(WorkflowRun::query()->where('status', 'pending')->count())->toBe(1)
        ->and(DB::table('leads')->where('id', $lead->id)->value('title'))->toBe('Enterprise deal');

    expect(app(WorkflowEngine::class)->processDue())->toBe(0);

    WorkflowRun::query()->update(['run_at' => now()->subMinute()]);

    expect(app(WorkflowEngine::class)->processDue())->toBe(1)
        ->and(DB::table('leads')->where('id', $lead->id)->value('title'))->toBe('Updated later')
        ->and(DB::table('activities')->where('title', 'After delay')->count())->toBe(1);
});

it('attributes a web form lead to a campaign and adds engagement points', function () {
    DB::table('lead_sources')->insert(['id' => 7, 'name' => 'google']);

    $campaign = Campaign::query()->create([
        'name' => 'Spring',
        'status' => 'active',
        'budget' => 500,
        'spent' => 125,
        'utm_campaign' => 'spring-launch',
        'source_name' => 'google',
    ]);

    $lead = marketingLead(['lead_source_id' => 1]);

    request()->merge([
        'utm_source' => 'google',
        'utm_medium' => 'cpc',
        'utm_campaign' => 'spring-launch',
        'ma_referrer' => 'https://ads.example',
    ]);

    app(AttributionRecorder::class)->capture($lead);

    $report = app(CampaignReport::class)->summarize($campaign->fresh());

    expect(DB::table('ma_attributions')->where('campaign_id', $campaign->id)->count())->toBe(1)
        ->and(DB::table('leads')->where('id', $lead->id)->value('lead_source_id'))->toBe(7)
        ->and(EngagementScore::query()->where('lead_id', $lead->id)->value('points'))->toBe(15)
        ->and($report['leads'])->toBe(1)
        ->and($report['remaining'])->toBe(375.0);
});

it('schedules a sequence, tracks delivery, and stops after unsubscribe', function () {
    Mail::fake();

    $audience = Audience::query()->create([
        'name' => 'Acme',
        'rules' => [['attribute' => 'name', 'operator' => 'contains', 'value' => 'Acme']],
    ]);

    DB::table('persons')->insert([
        ['id' => 1, 'name' => 'Acme Buyer', 'emails' => json_encode([['value' => 'buyer@acme.test']]), 'created_at' => now(), 'updated_at' => now()],
        ['id' => 2, 'name' => 'Other', 'emails' => json_encode([['value' => 'other@example.test']]), 'created_at' => now(), 'updated_at' => now()],
        ['id' => 3, 'name' => 'Acme Opt Out', 'emails' => json_encode([['value' => 'out@acme.test']]), 'created_at' => now(), 'updated_at' => now()],
    ]);

    DB::table('person_consents')->insert([
        'person_id' => 3,
        'channel' => 'email',
        'is_opted_in' => false,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('leads')->insert([
        'title' => 'Acme',
        'person_id' => 1,
        'user_id' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $sequence = EmailSequence::query()->create([
        'name' => 'Welcome',
        'status' => 'active',
        'audience_id' => $audience->id,
    ]);

    EmailStep::query()->create([
        'sequence_id' => $sequence->id,
        'sort' => 1,
        'delay_minutes' => 0,
        'subject' => 'Hello',
        'body' => 'First note',
        'link_url' => 'https://example.com/offer',
        'link_label' => 'Offer',
    ]);

    EmailStep::query()->create([
        'sequence_id' => $sequence->id,
        'sort' => 2,
        'delay_minutes' => 0,
        'subject' => 'Second',
        'body' => 'Second note',
    ]);

    expect(app(SequenceRunner::class)->enroll($sequence))->toBe(1);

    expect(app(SequenceRunner::class)->processDue())->toBe(1);
    Mail::assertSent(SequenceMail::class, 1);

    $message = EmailMessage::query()->first();
    app(TrackingService::class)->open($message->token);
    $response = app(TrackingService::class)->click($message->token);

    expect($response->getTargetUrl())->toBe('https://example.com/offer')
        ->and($message->fresh()->opened_at)->not->toBeNull()
        ->and($message->fresh()->clicked_at)->not->toBeNull()
        ->and(EngagementScore::query()->where('lead_id', 1)->value('points'))->toBe(15);

    $enrollment = EmailEnrollment::query()->first();
    app(EmailDispatcher::class)->unsubscribe($enrollment->unsubscribe_token);

    expect($enrollment->fresh()->status)->toBe('unsubscribed')
        ->and((int) DB::table('person_consents')->where('person_id', 1)->where('channel', 'email')->value('is_opted_in'))->toBe(0);

    EmailEnrollment::query()->whereKey($enrollment->id)->update([
        'status' => 'active',
        'next_run_at' => now()->subMinute(),
    ]);

    expect(app(SequenceRunner::class)->processDue())->toBe(0);
    Mail::assertSent(SequenceMail::class, 1);
});

it('adds delay, task, and otherwise actions onto an existing workflow', function () {
    $lead = marketingLead(['lead_value' => 50]);
    $workflow = (object) [
        'id' => 9,
        'actions' => [
            ['id' => 'ma_delay', 'attribute' => 'minutes', 'value' => '30'],
            ['id' => 'ma_task', 'attribute' => 'title', 'value' => 'After delay'],
            ['id' => 'ma_otherwise'],
            ['id' => 'ma_notification', 'attribute' => 'title', 'value' => 'Small lead'],
        ],
    ];

    $actions = app(CoreWorkflowActions::class);
    $actions->runMatch($workflow, $lead, function (): void {
        throw new RuntimeException('Core actions after a delay must wait.');
    });

    expect(DB::table('activities')->count())->toBe(0)
        ->and(CoreWorkflowRun::query()->where('status', 'pending')->count())->toBe(1);

    expect($actions->processDue())->toBe(0);

    CoreWorkflowRun::query()->update(['run_at' => now()->subMinute()]);

    expect($actions->processDue())->toBe(1)
        ->and(DB::table('activities')->where('title', 'After delay')->count())->toBe(1);

    $actions->runElse($workflow, marketingLead(['lead_value' => 10, 'title' => 'Tiny']), fn () => null);

    expect(DB::table('user_notifications')->where('title', 'Small lead')->count())->toBe(1);
});
