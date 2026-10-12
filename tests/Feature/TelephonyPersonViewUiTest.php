<?php

use Illuminate\Support\Facades\DB;
use Webkul\Contact\Models\Person;

it('renders the telephony settings page without a blank-screen view error', function () {
    $admin = getDefaultAdmin();

    test()->actingAs($admin)
        ->get(route('admin.telephony.settings.index'))
        ->assertOk()
        ->assertSee('Telephony Settings')
        ->assertSee('Account SID');
});

it('renders the telephony click-to-call and call history UI on the person view', function () {
    $admin = getDefaultAdmin();

    DB::table('core_config')->whereIn('code', [
        'telephony.credentials.account_sid',
        'telephony.credentials.auth_token',
    ])->delete();

    DB::table('core_config')->insert([
        ['code' => 'telephony.credentials.account_sid', 'value' => 'AC1234567890', 'created_at' => now(), 'updated_at' => now()],
        ['code' => 'telephony.credentials.auth_token', 'value' => 'test-auth-token', 'created_at' => now(), 'updated_at' => now()],
    ]);

    $person = Person::factory()->create([
        'name' => 'Jane Doe',
        'contact_numbers' => ['+1234567890'],
    ]);

    test()->actingAs($admin)
        ->get(route('admin.contacts.persons.view', $person->id))
        ->assertOk()
        ->assertSee('Call now')
        ->assertSee('Call history')
        ->assertSee('No calls yet');
});
