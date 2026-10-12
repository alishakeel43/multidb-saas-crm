<?php

namespace Webkul\UnifiedInbox\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Webkul\UnifiedInbox\Models\ChannelConnection;
use Webkul\UnifiedInbox\Models\Conversation;
use Webkul\UnifiedInbox\Models\Message;

/**
 * LOCAL DEMO DATA ONLY. Seeds dummy Meta channel connections + chats into the
 * current tenant DB. Tokens are fake; nothing here can call the real Graph API.
 *
 * Run:  php artisan tenants:artisan "db:seed --class=Webkul\\UnifiedInbox\\Database\\Seeders\\UnifiedInboxDemoSeeder --force" --tenant=1
 * Undo: set env UI_DEMO_SEED_PURGE=1 and run the same command (deletes only rows tagged metadata.demo_seed).
 */
class UnifiedInboxDemoSeeder extends Seeder
{
    protected const TAG = 'demo_seed';

    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->warn('UnifiedInboxDemoSeeder skipped in production.');

            return;
        }

        if (env('UI_DEMO_SEED_PURGE')) {
            $this->purge();

            return;
        }

        foreach ($this->dataset() as $channel => $set) {
            $connection = ChannelConnection::updateOrCreate(
                ['channel' => $channel, 'external_id' => $set['account_id']],
                [
                    'name' => $set['name'],
                    'status' => 'connected',
                    'credentials' => ['access_token' => 'dummy-access-token-'.$channel, 'token_type' => 'dummy'],
                    'metadata' => [self::TAG => true, 'note' => 'Local demo connection. Fake token.'],
                    'expires_at' => $channel === 'whatsapp' ? now()->addDays(60) : null,
                ]
            );

            foreach ($set['chats'] as $i => $chat) {
                $this->seedChat($channel, $connection->external_id, $i, $chat);
            }

            $this->command?->info(sprintf('%s: connection #%d, %d chats', $channel, $connection->id, count($set['chats'])));
        }
    }

    protected function seedChat(string $channel, string $accountId, int $i, array $chat): void
    {
        $base = Carbon::now()->subMinutes($chat['ago']);
        $conversation = Conversation::updateOrCreate(
            ['channel' => $channel, 'account_id' => $accountId, 'external_id' => $chat['id']],
            [
                'customer_name' => $chat['name'],
                'status' => $chat['status'] ?? 'open',
                'unread_count' => $chat['unread'] ?? 0,
                'metadata' => [self::TAG => true],
            ]
        );

        $last = null;
        $lastIn = null;
        $count = count($chat['messages']);
        foreach ($chat['messages'] as $n => [$direction, $body]) {
            $at = $base->copy()->subMinutes(($count - 1 - $n) * 7);
            Message::updateOrCreate(
                ['conversation_id' => $conversation->id, 'external_id' => "demo.{$channel}.{$i}.{$n}"],
                [
                    'direction' => $direction,
                    'body' => $body,
                    'status' => $direction === 'outbound' ? ($n === $count - 1 ? 'delivered' : 'read') : 'received',
                    'metadata' => [self::TAG => true],
                    'occurred_at' => $at,
                ]
            );
            $last = [$body, $at];
            if ($direction === 'inbound') {
                $lastIn = $at;
            }
        }

        $conversation->update([
            'last_message_preview' => mb_substr($last[0], 0, 120),
            'last_message_at' => $last[1],
            'last_incoming_at' => $lastIn,
        ]);
    }

    protected function purge(): void
    {
        $convs = Conversation::query()->get()->filter(fn ($c) => data_get($c->metadata, self::TAG));
        Message::whereIn('conversation_id', $convs->pluck('id'))->delete();
        Conversation::whereIn('id', $convs->pluck('id'))->delete();
        $conns = ChannelConnection::query()->get()->filter(fn ($c) => data_get($c->metadata, self::TAG));
        ChannelConnection::whereIn('id', $conns->pluck('id'))->delete();
        $this->command?->info("Purged {$convs->count()} demo chats and {$conns->count()} demo connections.");
    }

    protected function dataset(): array
    {
        return [
            'whatsapp' => [
                'account_id' => 'demo-waba-phone-000001',
                'name' => 'Demo WhatsApp (+1 555 000 0001)',
                'chats' => [
                    ['id' => '15550100001', 'name' => 'Julie Fisher', 'ago' => 5, 'unread' => 3, 'messages' => [
                        ['inbound', 'Hi, I need help with my order. Can you check the status?'],
                        ['outbound', "Hello Julie 👋\nSure, I'll check your order and get back to you shortly."],
                        ['inbound', 'Thank you! Also, can you confirm the delivery date?'],
                        ['outbound', 'Your order is expected to be delivered by 25th September. You will receive a tracking link soon.'],
                        ['inbound', 'Perfect, thanks!'],
                    ]],
                    ['id' => '15550100002', 'name' => 'Ayesha Khan', 'ago' => 95, 'unread' => 1, 'messages' => [
                        ['inbound', 'Hello, I would like to book a demo.'],
                        ['outbound', 'Happy to help! Which day works for you?'],
                        ['inbound', 'Please confirm my appointment for Friday.'],
                    ]],
                    ['id' => '15550100003', 'name' => 'Ali Raza', 'ago' => 1500, 'status' => 'pending', 'messages' => [
                        ['inbound', 'Do you offer custom plans?'],
                        ['outbound', 'Yes, we do. Could you share your team size?'],
                    ]],
                    ['id' => '15550100004', 'name' => 'Sara Ahmed', 'ago' => 3000, 'messages' => [
                        ['inbound', 'Is there a discount for annual billing?'],
                        ['outbound', 'Yes, annual plans include two months free.'],
                        ['inbound', 'Great, I will discuss with my team.'],
                    ]],
                    ['id' => '15550100005', 'name' => 'Omar Siddiqui', 'ago' => 6000, 'status' => 'closed', 'messages' => [
                        ['inbound', 'Issue resolved, thanks for the quick help.'],
                        ['outbound', 'Glad we could help, Omar!'],
                    ]],
                ],
            ],
            'messenger' => [
                'account_id' => 'demo-fb-page-000001',
                'name' => 'Demo Facebook Page',
                'chats' => [
                    ['id' => 'demo-psid-0001', 'name' => 'John Smith', 'ago' => 30, 'messages' => [
                        ['inbound', 'Hi, what are your opening hours?'],
                        ['outbound', 'We are open 9am–6pm, Monday to Saturday.'],
                        ['inbound', 'Thanks for the information!'],
                    ]],
                    ['id' => 'demo-psid-0002', 'name' => 'Emily Clark', 'ago' => 120, 'unread' => 2, 'messages' => [
                        ['inbound', 'Can I change my delivery address?'],
                        ['inbound', 'The order number is #10422.'],
                    ]],
                    ['id' => 'demo-psid-0003', 'name' => 'Hamza Tariq', 'ago' => 900, 'status' => 'pending', 'messages' => [
                        ['inbound', 'Do you ship to Lahore?'],
                        ['outbound', 'Yes, delivery takes 2–3 business days.'],
                        ['inbound', 'Okay, I will place the order tonight.'],
                    ]],
                    ['id' => 'demo-psid-0004', 'name' => 'Maria Lopez', 'ago' => 2800, 'messages' => [
                        ['inbound', 'Loved the product, thank you!'],
                        ['outbound', 'Thank you Maria, we appreciate it!'],
                    ]],
                ],
            ],
            'instagram' => [
                'account_id' => 'demo-ig-account-000001',
                'name' => 'Demo Instagram (@zaris.demo)',
                'chats' => [
                    ['id' => 'demo-igsid-0001', 'name' => 'Falah Bashir Mughal', 'ago' => 60, 'unread' => 1, 'messages' => [
                        ['inbound', 'Hi! Can you share pricing details?'],
                        ['outbound', 'Sure! Our plans start at $19/month. Want the full sheet?'],
                        ['inbound', 'Yes please, send it over.'],
                    ]],
                    ['id' => 'demo-igsid-0002', 'name' => 'Zainab Malik', 'ago' => 400, 'messages' => [
                        ['inbound', 'Saw your latest post, is this available in blue?'],
                        ['outbound', 'Yes, blue is in stock in all sizes.'],
                    ]],
                    ['id' => 'demo-igsid-0003', 'name' => 'Usman Ghani', 'ago' => 1700, 'unread' => 2, 'messages' => [
                        ['inbound', 'Do you do collaborations?'],
                        ['inbound', 'I run a tech page with 40k followers.'],
                    ]],
                    ['id' => 'demo-igsid-0004', 'name' => 'Hira Shah', 'ago' => 4300, 'status' => 'closed', 'messages' => [
                        ['inbound', 'Got my parcel today 😊'],
                        ['outbound', 'Yay! Enjoy, Hira.'],
                    ]],
                ],
            ],
        ];
    }
}
