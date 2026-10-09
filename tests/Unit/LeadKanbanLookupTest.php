<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use Webkul\Admin\Http\Controllers\Lead\LeadController;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Contact\Repositories\PersonRepository;
use Webkul\Lead\Repositories\LeadRepository;
use Webkul\Lead\Repositories\PipelineRepository;
use Webkul\Lead\Repositories\ProductRepository;
use Webkul\Lead\Repositories\SourceRepository;
use Webkul\Lead\Repositories\StageRepository;
use Webkul\Lead\Repositories\TypeRepository;
use Webkul\Quote\Repositories\QuoteItemRepository;
use Webkul\Quote\Repositories\QuoteRepository;
use Webkul\User\Repositories\UserRepository;

uses(TestCase::class);

beforeEach(function () {
    config([
        'database.default' => 'tenant',
        'database.connections.tenant' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ],
    ]);

    DB::purge('tenant');

    Schema::connection('tenant')->create('users', function (Blueprint $table) {
        $table->increments('id');
        $table->string('name');
        $table->string('email');
        $table->boolean('status');
        $table->boolean('is_support_account')->default(false);
    });

    DB::connection('tenant')->table('users')->insert([
        ['id' => 1, 'name' => 'Alice', 'email' => 'alice@example.test', 'status' => true, 'is_support_account' => false],
        ['id' => 2, 'name' => 'Alan', 'email' => 'alan@example.test', 'status' => false, 'is_support_account' => false],
        ['id' => 3, 'name' => 'Alex', 'email' => 'alex@example.test', 'status' => true, 'is_support_account' => false],
        ['id' => 4, 'name' => 'Alison', 'email' => 'alison@example.test', 'status' => true, 'is_support_account' => true],
    ]);

    app()->instance('bouncer', new class
    {
        public function getAuthorizedUserIds(): array
        {
            return [1, 2, 4];
        }
    });

    app()->instance('request', Request::create('/admin/leads/kanban/look-up', 'GET', [
        'column' => 'user_id',
        'search' => 'Al',
    ]));
});

afterEach(function () {
    DB::purge('tenant');
});

it('limits lead kanban assignee lookup to active authorized non-support users', function () {
    $emptyRepository = function (string $class) {
        $repository = Mockery::mock($class);
        $repository->shouldReceive('all')->andReturn(collect());

        return $repository;
    };

    $controller = new LeadController(
        app(UserRepository::class),
        Mockery::mock(AttributeRepository::class),
        $emptyRepository(SourceRepository::class),
        $emptyRepository(TypeRepository::class),
        Mockery::mock(PipelineRepository::class),
        Mockery::mock(StageRepository::class),
        Mockery::mock(LeadRepository::class),
        Mockery::mock(ProductRepository::class),
        Mockery::mock(QuoteItemRepository::class),
        Mockery::mock(QuoteRepository::class),
        Mockery::mock(PersonRepository::class),
    );

    $results = $controller->kanbanLookup();

    expect($results->pluck('label')->all())->toBe(['Alice']);
});
