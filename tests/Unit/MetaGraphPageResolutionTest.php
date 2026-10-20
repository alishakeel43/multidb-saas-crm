<?php

use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use Webkul\UnifiedInbox\Services\MetaGraphClient;

uses(TestCase::class);

beforeEach(function () {
    config([
        'unified-inbox.meta.app_id' => '1234567890123456',
        'unified-inbox.meta.app_secret' => 'test-app-secret',
        'unified-inbox.meta.version' => 'v21.0',
    ]);
});

it('reads an instagram messaging profile picture from profile_pic', function () {
    Http::fake([
        'graph.facebook.com/*' => Http::response([
            'name' => 'Ali',
            'username' => 'ali.shakeel.35',
            'profile_pic' => 'https://cdn.example.test/ig.jpg',
        ]),
    ]);

    $profile = app(MetaGraphClient::class)->messagingUserProfile('1664347248679585', 'page-token', 'instagram');

    expect($profile['name'])->toBe('Ali')
        ->and($profile['profile_pic'])->toBe('https://cdn.example.test/ig.jpg');

    Http::assertSent(fn ($request) => str_contains(urldecode($request->url()), 'profile_pic'));
});

it('reads page ids from facebook login for business granular scopes', function () {
    Http::fake([
        'graph.facebook.com/*debug_token*' => Http::response([
            'data' => [
                'granular_scopes' => [
                    [
                        'scope' => 'pages_messaging',
                        'target_ids' => ['111', '222'],
                    ],
                    [
                        'scope' => 'instagram_manage_messages',
                        'target_ids' => ['999'],
                    ],
                    [
                        'scope' => 'email',
                    ],
                ],
            ],
        ]),
    ]);

    $ids = app(MetaGraphClient::class)->pageIdsFromGranularScopes('user-token');

    expect($ids)->toBe(['111', '222']);
});

it('falls back to granular scope pages when me/accounts is empty', function () {
    Http::fake([
        'graph.facebook.com/*/me/accounts*' => Http::response(['data' => []]),
        'graph.facebook.com/*/me/assigned_pages*' => Http::response(['data' => []]),
        'graph.facebook.com/*/me/businesses*' => Http::response(['data' => []]),
        'graph.facebook.com/*debug_token*' => Http::response([
            'data' => [
                'granular_scopes' => [
                    [
                        'scope' => 'pages_show_list',
                        'target_ids' => ['555'],
                    ],
                ],
            ],
        ]),
        'graph.facebook.com/*/555*' => Http::response([
            'id' => '555',
            'name' => 'Zaris Page',
            'access_token' => 'page-token',
            'instagram_business_account' => [
                'id' => 'ig-1',
                'username' => 'zaris',
            ],
        ]),
    ]);

    $pages = app(MetaGraphClient::class)->getFacebookPages('user-token');

    expect($pages)->toHaveCount(1)
        ->and($pages[0]['id'])->toBe('555')
        ->and($pages[0]['name'])->toBe('Zaris Page')
        ->and($pages[0]['instagram_business_account']['id'])->toBe('ig-1');
});

it('uses embedded signup page ids when provided', function () {
    Http::fake([
        'graph.facebook.com/*/777*' => Http::response([
            'id' => '777',
            'name' => 'Preferred Page',
            'access_token' => 'page-token',
        ]),
    ]);

    $pages = app(MetaGraphClient::class)->getFacebookPages('user-token', ['777']);

    expect($pages)->toHaveCount(1)
        ->and($pages[0]['id'])->toBe('777');
});

it('falls back to assigned pages for business-managed assets', function () {
    Http::fake([
        'graph.facebook.com/*/me/accounts*' => Http::response(['data' => []]),
        'graph.facebook.com/*/me/assigned_pages*' => Http::response([
            'data' => [
                [
                    'id' => '888',
                    'name' => 'Assigned Page',
                    'access_token' => 'assigned-token',
                ],
            ],
        ]),
    ]);

    $pages = app(MetaGraphClient::class)->getFacebookPages('user-token');

    expect($pages)->toHaveCount(1)
        ->and($pages[0]['id'])->toBe('888');
});
