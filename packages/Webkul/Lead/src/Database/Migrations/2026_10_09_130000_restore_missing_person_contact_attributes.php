<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Restore standard person contact attributes required by inline validation.
     */
    public function up(): void
    {
        $now = now();
        $locale = config('app.locale');

        foreach ([
            [
                'code' => 'emails',
                'name' => trans('installer::app.seeders.attributes.persons.emails', [], $locale),
                'type' => 'email',
                'validation' => null,
                'sort_order' => 2,
                'is_required' => true,
                'is_unique' => true,
            ],
            [
                'code' => 'contact_numbers',
                'name' => trans('installer::app.seeders.attributes.persons.contact-numbers', [], $locale),
                'type' => 'phone',
                'validation' => 'numeric',
                'sort_order' => 3,
                'is_required' => false,
                'is_unique' => true,
            ],
        ] as $attribute) {
            DB::table('attributes')->insertOrIgnore([
                ...$attribute,
                'entity_type' => 'persons',
                'lookup_type' => null,
                'quick_add' => true,
                'is_user_defined' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /**
     * Keep restored system attributes so tenant records remain valid.
     */
    public function down(): void
    {
        //
    }
};
