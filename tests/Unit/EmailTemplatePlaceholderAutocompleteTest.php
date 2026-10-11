<?php

use Tests\TestCase;
use Webkul\Automation\Helpers\EmailTemplatePlaceholderAutocomplete;

uses(TestCase::class);

function placeholderFixture(): array
{
    return [
        [
            'text' => 'Leads',
            'menu' => [
                ['text' => 'Title', 'value' => '{%leads.title%}'],
                ['text' => 'Lead Value', 'value' => '{%leads.lead_value%}'],
                ['text' => 'Description', 'value' => '{%leads.description%}'],
            ],
        ],
        [
            'text' => 'Persons',
            'menu' => [
                ['text' => 'Name', 'value' => '{%persons.name%}'],
            ],
        ],
    ];
}

it('builds a catalog of entities and attributes from placeholders', function () {
    $catalog = app(EmailTemplatePlaceholderAutocomplete::class)->catalog(placeholderFixture());

    expect($catalog)->toHaveCount(2)
        ->and($catalog[0]['type'])->toBe('leads')
        ->and($catalog[0]['label'])->toBe('Leads')
        ->and($catalog[0]['attributes'])->toHaveCount(3)
        ->and($catalog[0]['attributes'][0]['code'])->toBe('title');
});

it('suggests matching entities while typing after the placeholder trigger', function () {
    $helper = app(EmailTemplatePlaceholderAutocomplete::class);
    $catalog = $helper->catalog(placeholderFixture());

    $suggestions = $helper->suggest('l', $catalog);

    expect($suggestions)->toHaveCount(1)
        ->and($suggestions[0]['insert'])->toBe('{%leads.')
        ->and($suggestions[0]['kind'])->toBe('entity');
});

it('suggests lead attributes after typing entity and a field prefix', function () {
    $helper = app(EmailTemplatePlaceholderAutocomplete::class);
    $catalog = $helper->catalog(placeholderFixture());

    $suggestions = $helper->suggest('leads.t', $catalog);

    expect(collect($suggestions)->pluck('insert')->all())
        ->toContain('{%leads.title%}')
        ->not->toContain('{%leads.description%}')
        ->and($suggestions[0]['kind'])->toBe('attribute');
});

it('lists all attributes for an entity when only the trailing dot is typed', function () {
    $helper = app(EmailTemplatePlaceholderAutocomplete::class);
    $catalog = $helper->catalog(placeholderFixture());

    $suggestions = $helper->suggest('leads.', $catalog);

    expect($suggestions)->toHaveCount(3);
});
