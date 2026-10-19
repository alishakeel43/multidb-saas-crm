<?php

use Illuminate\Support\Facades\DB;
use Webkul\Attribute\Models\Attribute;

beforeEach(function () {
    $database = (string) DB::connection('tenant')->getDatabaseName();

    if (! str_starts_with($database, 'tenant_test')) {
        throw new RuntimeException("LeadAiFieldsCommandTest refused to run against database [{$database}]. Use the isolated test DB (see phpunit.xml).");
    }

    DB::connection('tenant')->beginTransaction();
});

afterEach(function () {
    $connection = DB::connection('tenant');

    while ($connection->transactionLevel() > 0) {
        $connection->rollBack();
    }
});

it('adds the AI lead fields as user-defined attributes and is idempotent', function () {
    $this->artisan('crm:lead-ai-fields')->assertSuccessful();
    $this->artisan('crm:lead-ai-fields')->assertSuccessful();

    $fields = Attribute::where('entity_type', 'leads')
        ->where('code', 'fit_score')
        ->get();

    expect($fields)->toHaveCount(1)
        ->and($fields->first()->is_user_defined)->toBeTruthy()
        ->and($fields->first()->options()->pluck('name')->all())->toBe(['A', 'B', 'C']);

    expect(Attribute::where('entity_type', 'leads')->where('code', 'human_approved')->value('type'))->toBe('boolean')
        ->and(Attribute::where('entity_type', 'leads')->where('code', 'ai_confidence')->value('validation'))->toBe('numeric');
});

it('removes only the AI lead fields', function () {
    $this->artisan('crm:lead-ai-fields')->assertSuccessful();
    $this->artisan('crm:lead-ai-fields', ['--remove' => true])->assertSuccessful();

    expect(Attribute::where('entity_type', 'leads')->where('code', 'fit_score')->exists())->toBeFalse()
        ->and(Attribute::where('entity_type', 'leads')->where('code', 'title')->exists())->toBeTrue();
});
