<?php

use Tests\TestCase;
use Webkul\Contact\Repositories\PersonRepository;

uses(TestCase::class);

it('generates a person unique ID when optional contact numbers are empty', function (array $contactNumbers) {
    $repository = (new ReflectionClass(PersonRepository::class))->newInstanceWithoutConstructor();
    $method = new ReflectionMethod(PersonRepository::class, 'sanitizeRequestedPersonData');
    $method->setAccessible(true);

    $data = $method->invoke($repository, [
        'emails' => [['value' => 'person@example.test', 'label' => 'work']],
        'contact_numbers' => $contactNumbers,
    ]);

    expect($data['unique_id'])->toBe('person@example.test')
        ->and($data['contact_numbers'])->toBe([]);
})->with([
    'empty list' => [[]],
    'only blank number' => [[['value' => null, 'label' => 'work']]],
]);

it('includes a non-empty contact number in the person unique ID', function () {
    $repository = (new ReflectionClass(PersonRepository::class))->newInstanceWithoutConstructor();
    $method = new ReflectionMethod(PersonRepository::class, 'sanitizeRequestedPersonData');
    $method->setAccessible(true);

    $data = $method->invoke($repository, [
        'emails' => [['value' => 'person@example.test', 'label' => 'work']],
        'contact_numbers' => [['value' => '+15551234567', 'label' => 'work']],
    ]);

    expect($data['unique_id'])->toBe('person@example.test|+15551234567');
});
