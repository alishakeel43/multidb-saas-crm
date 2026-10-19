<?php

use Tests\TestCase;
use Webkul\UnifiedInbox\Repositories\ChannelConnectionRepository;

uses(TestCase::class);

it('shows the newest connected account instead of an older disconnected row', function () {
    $connections = collect([
        (object) ['id' => 1, 'channel' => 'whatsapp', 'status' => 'disconnected', 'name' => 'Old'],
        (object) ['id' => 2, 'channel' => 'whatsapp', 'status' => 'connected', 'name' => 'Middle'],
        (object) ['id' => 4, 'channel' => 'whatsapp', 'status' => 'connected', 'name' => 'Latest'],
        (object) ['id' => 3, 'channel' => 'messenger', 'status' => 'connected', 'name' => 'Page'],
    ]);

    $picker = app(ChannelConnectionRepository::class);

    expect($picker->preferredForChannel($connections, 'whatsapp')->name)->toBe('Latest')
        ->and($picker->preferredForChannel($connections, 'messenger')->name)->toBe('Page')
        ->and($picker->preferredForChannel($connections, 'instagram'))->toBeNull();
});
