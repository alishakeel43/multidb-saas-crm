<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use Webkul\Lead\Models\Lead;
use Webkul\LeadScoring\Models\LeadScoringRule;
use Webkul\LeadScoring\Services\LeadScoringService;

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

    Schema::create('leads', function (Blueprint $table) {
        $table->increments('id');
        $table->string('title')->nullable();
        $table->decimal('lead_value', 12, 2)->default(0);
        $table->integer('score')->default(0);
        $table->integer('status')->default(1);
        $table->timestamps();
    });

    Schema::create('lead_scoring_rules', function (Blueprint $table) {
        $table->increments('id');
        $table->string('name');
        $table->string('field');
        $table->string('operator', 32);
        $table->string('value')->nullable();
        $table->integer('points')->default(0);
        $table->boolean('is_active')->default(true);
        $table->unsignedInteger('priority')->default(0);
        $table->timestamps();
    });
});

it('sums matching rule points onto the lead score', function () {
    LeadScoringRule::query()->create([
        'name' => 'High value',
        'field' => 'lead_value',
        'operator' => 'gte',
        'value' => '1000',
        'points' => 20,
        'is_active' => true,
        'priority' => 1,
    ]);

    LeadScoringRule::query()->create([
        'name' => 'Hot title',
        'field' => 'title',
        'operator' => 'contains',
        'value' => 'enterprise',
        'points' => 10,
        'is_active' => true,
        'priority' => 1,
    ]);

    $leadId = DB::table('leads')->insertGetId([
        'title' => 'Enterprise deal',
        'lead_value' => 2500,
        'score' => 0,
        'status' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $lead = Lead::withoutEvents(fn () => Lead::query()->findOrFail($leadId));

    $score = app(LeadScoringService::class)->score($lead);

    expect($score)->toBe(30)
        ->and((int) DB::table('leads')->where('id', $leadId)->value('score'))->toBe(30);
});
