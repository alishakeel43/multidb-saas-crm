<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use Webkul\Quote\Models\Quote;
use Webkul\QuoteApproval\Models\QuoteApproval;
use Webkul\QuoteApproval\Services\QuoteApprovalService;

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

    Schema::create('quotes', function (Blueprint $table) {
        $table->increments('id');
        $table->string('subject')->nullable();
        $table->unsignedInteger('user_id')->nullable();
        $table->unsignedInteger('person_id')->nullable();
        $table->timestamps();
    });

    Schema::create('quote_approvals', function (Blueprint $table) {
        $table->increments('id');
        $table->unsignedInteger('quote_id')->unique();
        $table->string('status')->default('draft');
        $table->unsignedInteger('requested_by')->nullable();
        $table->unsignedInteger('reviewed_by')->nullable();
        $table->timestamp('requested_at')->nullable();
        $table->timestamp('reviewed_at')->nullable();
        $table->text('notes')->nullable();
        $table->string('delivery_status', 32)->default('none');
        $table->string('order_reference')->nullable();
        $table->timestamp('converted_at')->nullable();
        $table->timestamps();
    });
});

it('moves a quote through submit and approve', function () {
    $quote = Quote::query()->create(['subject' => 'Proposal']);
    $service = app(QuoteApprovalService::class);

    $draft = $service->forQuote($quote);
    expect($draft->status)->toBe(QuoteApproval::STATUS_DRAFT);

    $pending = $service->submit($quote);
    expect($pending->status)->toBe(QuoteApproval::STATUS_PENDING);

    $approved = $service->approve($quote, 'Looks good');
    expect($approved->status)->toBe(QuoteApproval::STATUS_APPROVED)
        ->and($service->isApproved($quote))->toBeTrue();
});

it('rejects a pending quote', function () {
    $quote = Quote::query()->create(['subject' => 'Proposal']);
    $service = app(QuoteApprovalService::class);

    $service->submit($quote);
    $rejected = $service->reject($quote, 'Too high');

    expect($rejected->status)->toBe(QuoteApproval::STATUS_REJECTED);
});

it('marks approved quotes delivered and converted', function () {
    $quote = Quote::query()->create(['subject' => 'Proposal']);
    $service = app(QuoteApprovalService::class);

    $service->submit($quote);
    $service->approve($quote);

    $delivered = $service->markDelivered($quote);
    expect($delivered->delivery_status)->toBe(QuoteApproval::DELIVERY_SENT);

    $converted = $service->convertToOrder($quote, 'ORD-100');
    expect($converted->delivery_status)->toBe(QuoteApproval::DELIVERY_CONVERTED)
        ->and($converted->order_reference)->toBe('ORD-100')
        ->and($converted->converted_at)->not->toBeNull();
});
