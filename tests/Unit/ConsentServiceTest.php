<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use Webkul\Consent\Services\ConsentService;
use Webkul\Contact\Contracts\Person as PersonContract;

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

    Schema::create('persons', function (Blueprint $table) {
        $table->increments('id');
        $table->string('name')->nullable();
        $table->json('emails')->nullable();
        $table->json('contact_numbers')->nullable();
        $table->timestamps();
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
});

function makeTestPerson(string $name = 'Pat'): PersonContract
{
    $person = new class extends Model implements PersonContract
    {
        protected $table = 'persons';

        protected $guarded = [];

        public $timestamps = true;
    };

    $person->fill(['name' => $name])->save();

    return $person->fresh();
}

it('stores opt-in and opt-out preferences per channel', function () {
    $person = makeTestPerson();
    $service = app(ConsentService::class);

    $service->set($person, 'email', true, 'webform');
    expect($service->isOptedIn($person, 'email'))->toBeTrue()
        ->and($service->canMarket($person, 'email'))->toBeTrue();

    $service->set($person, 'email', false, 'unsubscribe');
    expect($service->isOptedIn($person, 'email'))->toBeFalse()
        ->and($service->canMarket($person, 'email'))->toBeFalse();
});

it('treats missing consent as not marketable', function () {
    $person = makeTestPerson();

    expect(app(ConsentService::class)->canMarket($person, 'sms'))->toBeFalse();
});
