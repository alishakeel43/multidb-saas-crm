<?php

use Tests\TestCase;
use Webkul\UnifiedInbox\Services\MetaOAuthCoordinator;

uses(TestCase::class);

it('builds messenger selectable assets from facebook pages', function () {
    $assets = app(MetaOAuthCoordinator::class)->buildSelectableAssets('messenger', [
        [
            'id' => '111',
            'name' => 'Zaris Page',
            'access_token' => 'page-token',
            'instagram_business_account' => ['id' => 'ig-1', 'username' => 'zaris'],
        ],
        [
            'id' => '222',
            'name' => 'Other Page',
            'access_token' => 'other-token',
        ],
    ], 'user-token');

    expect($assets)->toHaveCount(2)
        ->and($assets[0]['id'])->toBe('111')
        ->and($assets[0]['name'])->toBe('Zaris Page')
        ->and($assets[1]['id'])->toBe('222');
});

it('builds instagram selectable assets only when a page has a linked ig account', function () {
    $assets = app(MetaOAuthCoordinator::class)->buildSelectableAssets('instagram', [
        [
            'id' => '111',
            'name' => 'Zaris Page',
            'access_token' => 'page-token',
            'instagram_business_account' => ['id' => 'ig-9', 'username' => 'zaris.ig'],
        ],
        [
            'id' => '222',
            'name' => 'No IG Page',
            'access_token' => 'other-token',
        ],
    ], 'user-token');

    expect($assets)->toHaveCount(1)
        ->and($assets[0]['id'])->toBe('ig-9')
        ->and($assets[0]['name'])->toBe('@zaris.ig')
        ->and($assets[0]['page_id'])->toBe('111');
});

it('builds one central oauth redirect url for every tenant', function () {
    config([
        'app.url' => 'https://saas.zaristech.com',
        'unified-inbox.webhook_url' => 'https://saas.zaristech.com/api/meta/webhook',
        'unified-inbox.oauth_redirect_url' => null,
    ]);

    expect(app(MetaOAuthCoordinator::class)->getCentralOAuthRedirectUrl())
        ->toBe('https://saas.zaristech.com/api/meta/oauth/callback');
});
