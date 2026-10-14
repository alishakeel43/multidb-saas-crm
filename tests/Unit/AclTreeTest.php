<?php

use Tests\TestCase;

uses(TestCase::class);

it('builds the acl tree without missing intermediate keys', function () {
    $items = acl()->getItems();

    expect($items)->not->toBeEmpty();

    $keys = collect(config('acl'))->pluck('key')->filter()->all();
    $missingParents = [];

    foreach ($keys as $key) {
        $parts = explode('.', $key);
        $path = [];

        for ($i = 0; $i < count($parts) - 1; $i++) {
            $path[] = $parts[$i];
            $parent = implode('.', $path);

            if (! in_array($parent, $keys, true)) {
                $missingParents[$parent] = true;
            }
        }
    }

    expect($missingParents)->toBeEmpty();
});

it('includes telephony call log and settings branches in the acl tree', function () {
    $keys = [];

    $walk = function ($items) use (&$walk, &$keys): void {
        foreach ($items as $item) {
            $keys[] = $item->key;
            $walk($item->children);
        }
    };

    $walk(acl()->getItems());

    expect($keys)->toContain('telephony')
        ->and($keys)->toContain('telephony.call_logs')
        ->and($keys)->toContain('telephony.call_logs.view')
        ->and($keys)->toContain('telephony.settings')
        ->and($keys)->toContain('telephony.settings.edit');
});
