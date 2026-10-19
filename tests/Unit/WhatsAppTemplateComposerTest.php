<?php

use Webkul\UnifiedInbox\Support\WhatsAppTemplateComposer;

it('builds text variables for a whatsapp template and rejects a media header', function () {
    $components = [
        ['type' => 'HEADER', 'format' => 'TEXT', 'text' => 'Hello {{1}}'],
        ['type' => 'BODY', 'text' => 'Your code is {{1}} for {{2}}'],
        ['type' => 'FOOTER', 'text' => 'Ignore {{1}}'],
    ];

    $slots = WhatsAppTemplateComposer::slots($components);

    expect($slots)->toHaveCount(3)
        ->and(WhatsAppTemplateComposer::requiresMedia($components))->toBeFalse()
        ->and(WhatsAppTemplateComposer::components($slots, ['Ayesha', '4821', 'today']))->toBe([
            [
                'type' => 'header',
                'parameters' => [
                    ['type' => 'text', 'text' => 'Ayesha'],
                ],
            ],
            [
                'type' => 'body',
                'parameters' => [
                    ['type' => 'text', 'text' => '4821'],
                    ['type' => 'text', 'text' => 'today'],
                ],
            ],
        ])
        ->and(WhatsAppTemplateComposer::phone('+1 (512) 831-3462'))->toBe('15128313462');

    expect(WhatsAppTemplateComposer::requiresMedia([
        ['type' => 'HEADER', 'format' => 'IMAGE'],
    ]))->toBeTrue();
});
