<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use Webkul\Assignment\Models\AssignmentRule;
use Webkul\Assignment\Services\LeadAssignmentService;
use Webkul\Lead\Contracts\Lead as LeadContract;
use Webkul\User\Models\User;

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

    Schema::create('users', function (Blueprint $table) {
        $table->increments('id');
        $table->string('name')->nullable();
        $table->string('email')->nullable();
        $table->string('password')->nullable();
        $table->boolean('status')->default(1);
        $table->timestamps();
    });

    Schema::create('leads', function (Blueprint $table) {
        $table->increments('id');
        $table->string('title')->nullable();
        $table->unsignedInteger('user_id')->nullable();
        $table->unsignedInteger('person_id')->nullable();
        $table->unsignedInteger('lead_source_id')->nullable();
        $table->unsignedInteger('lead_type_id')->nullable();
        $table->boolean('status')->default(1);
        $table->timestamps();
    });

    Schema::create('assignment_rules', function (Blueprint $table) {
        $table->increments('id');
        $table->string('name');
        $table->string('entity_type')->default('leads');
        $table->string('strategy');
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
    });

    Schema::create('assignment_cursors', function (Blueprint $table) {
        $table->increments('id');
        $table->unsignedInteger('assignment_rule_id');
        $table->unsignedInteger('last_user_id')->nullable();
        $table->timestamps();
    });
});

function makeTestLead(array $attributes = []): LeadContract
{
    $lead = new class extends Model implements LeadContract
    {
        protected $table = 'leads';

        protected $guarded = [];

        public $timestamps = true;
    };

    $lead->fill(array_merge(['title' => 'Lead', 'status' => 1], $attributes))->save();

    return $lead->fresh();
}

it('assigns leads round-robin across active rule users', function () {
    $alice = User::query()->create(['name' => 'Alice', 'email' => 'a@example.com', 'password' => 'x', 'status' => 1]);
    $bob = User::query()->create(['name' => 'Bob', 'email' => 'b@example.com', 'password' => 'x', 'status' => 1]);

    $rule = AssignmentRule::query()->create([
        'name' => 'Default RR',
        'entity_type' => 'leads',
        'strategy' => 'round_robin',
        'is_active' => true,
        'only_when_empty' => true,
        'priority' => 10,
    ]);

    $rule->users()->attach([
        $alice->id => ['sort_order' => 0, 'is_active' => true],
        $bob->id => ['sort_order' => 1, 'is_active' => true],
    ]);

    $service = app(LeadAssignmentService::class);

    $lead1 = makeTestLead(['title' => 'L1']);
    $lead2 = makeTestLead(['title' => 'L2']);
    $lead3 = makeTestLead(['title' => 'L3']);

    expect($service->assignIfNeeded($lead1)?->id)->toBe($alice->id)
        ->and($service->assignIfNeeded($lead2)?->id)->toBe($bob->id)
        ->and($service->assignIfNeeded($lead3)?->id)->toBe($alice->id)
        ->and($lead1->fresh()->user_id)->toBe($alice->id);
});

it('skips assignment when lead already has an owner and only_when_empty is true', function () {
    $alice = User::query()->create(['name' => 'Alice', 'email' => 'a@example.com', 'password' => 'x', 'status' => 1]);
    $bob = User::query()->create(['name' => 'Bob', 'email' => 'b@example.com', 'password' => 'x', 'status' => 1]);

    $rule = AssignmentRule::query()->create([
        'name' => 'Default RR',
        'entity_type' => 'leads',
        'strategy' => 'round_robin',
        'is_active' => true,
        'only_when_empty' => true,
        'priority' => 10,
    ]);

    $rule->users()->attach([$alice->id => ['sort_order' => 0, 'is_active' => true]]);

    $lead = makeTestLead(['title' => 'Owned', 'user_id' => $bob->id]);

    expect(app(LeadAssignmentService::class)->assignIfNeeded($lead))->toBeNull()
        ->and($lead->fresh()->user_id)->toBe($bob->id);
});
