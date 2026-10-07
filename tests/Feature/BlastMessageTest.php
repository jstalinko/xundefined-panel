<?php

namespace Tests\Feature;

use App\Models\BlastMessage;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BlastMessageTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        $this->admin = User::where('email', 'admin@admin.com')->first();
        $this->admin->update(['telegram_id' => '100000001', 'role' => 'admin']);

        $this->regularUser = User::create([
            'name' => 'Regular Operative',
            'email' => 'operative@user.local',
            'password' => bcrypt('password123'),
            'role' => 'user',
            'telegram_id' => '200000002',
        ]);
    }

    public function test_guest_cannot_access_blast_message_page(): void
    {
        $response = $this->get('/xingzheng-panel/blast-message');
        $response->assertRedirect('/login');
    }

    public function test_non_admin_cannot_access_blast_message_page(): void
    {
        $response = $this->actingAs($this->regularUser)->get('/xingzheng-panel/blast-message');
        $response->assertRedirect('/');
    }

    public function test_admin_can_access_blast_message_page_and_see_subscriber_stats(): void
    {
        $response = $this->actingAs($this->admin)->get('/xingzheng-panel/blast-message');
        $response->assertStatus(200);
        $response->assertSee('TELEGRAM MESSAGE BROADCAST MAINFRAME');
        $response->assertSee('COMPOSE TELEGRAM BROADCAST');
        $response->assertSee('BROADCAST HISTORY');
        $response->assertSee('SUBSCRIBERS');
    }

    public function test_admin_can_preview_recipients_count_via_ajax(): void
    {
        $response = $this->actingAs($this->admin)->getJson('/xingzheng-panel/blast-message/recipients-preview?target=all');
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'count' => 2, // admin and regularUser both have telegram_id
        ]);

        $responseMembers = $this->actingAs($this->admin)->getJson('/xingzheng-panel/blast-message/recipients-preview?target=members');
        $responseMembers->assertStatus(200);
        $responseMembers->assertJson([
            'success' => true,
            'count' => 1,
        ]);
    }

    public function test_admin_can_broadcast_message_to_all_telegram_subscribers(): void
    {
        Http::fake([
            'https://api.telegram.org/bot*/sendMessage' => Http::response([
                'ok' => true,
                'result' => [
                    'message_id' => 778899,
                    'chat' => ['id' => 123],
                ],
            ], 200),
        ]);

        // Add 2 more users with telegram_id
        User::create([
            'name' => 'User Two',
            'email' => 'user2@test.local',
            'telegram_id' => '300000003',
            'role' => 'user',
        ]);
        User::create([
            'name' => 'User Three',
            'email' => 'user3@test.local',
            'telegram_id' => '400000004',
            'role' => 'user',
        ]);

        $payload = [
            'title' => 'Important Security Notice',
            'message' => 'Hello *Operatives*, please check the latest platform updates!',
            'target_audience' => 'all',
            'parse_mode' => 'Markdown',
        ];

        $response = $this->actingAs($this->admin)->post('/xingzheng-panel/blast-message/send', $payload);

        $response->assertRedirect('/xingzheng-panel/blast-message');
        $response->assertSessionHas('status');

        // Check BlastMessage record
        $this->assertDatabaseHas('blast_messages', [
            'title' => 'Important Security Notice',
            'target_audience' => 'all',
            'status' => 'completed',
            'total_recipients' => 4,
            'successful_count' => 4,
            'failed_count' => 0,
        ]);

        // Ensure Telegram API was contacted for each recipient
        Http::assertSent(function (Request $request) {
            return str_contains($request->url(), 'sendMessage')
                && str_contains($request['text'], 'Hello *Operatives*');
        });

        // Ensure activity log was recorded
        $this->assertDatabaseHas('activities', [
            'action' => 'BLAST_MESSAGE',
            'user_id' => $this->admin->id,
        ]);
    }

    public function test_admin_can_broadcast_to_members_only(): void
    {
        Http::fake([
            'https://api.telegram.org/bot*/sendMessage' => Http::response([
                'ok' => true,
                'result' => ['message_id' => 12345],
            ], 200),
        ]);

        $payload = [
            'title' => 'Member Exclusive Offer',
            'message' => 'Exclusive promo code for operatives!',
            'target_audience' => 'members',
            'parse_mode' => 'Markdown',
        ];

        $response = $this->actingAs($this->admin)->post('/xingzheng-panel/blast-message/send', $payload);
        $response->assertRedirect('/xingzheng-panel/blast-message');

        $blast = BlastMessage::latest()->first();
        $this->assertNotNull($blast);
        $this->assertEquals('members', $blast->target_audience);
        $this->assertEquals(1, $blast->total_recipients);
        $this->assertEquals(1, $blast->successful_count);
    }

    public function test_admin_can_broadcast_to_custom_chat_ids(): void
    {
        Http::fake([
            'https://api.telegram.org/bot*/sendMessage' => Http::response([
                'ok' => true,
                'result' => ['message_id' => 1122],
            ], 200),
        ]);

        $payload = [
            'title' => 'Direct VIP Blast',
            'message' => 'Custom message for specified chat IDs.',
            'target_audience' => 'custom',
            'custom_chat_ids' => "555111222, 777888999\n999000111",
            'parse_mode' => 'plain',
        ];

        $response = $this->actingAs($this->admin)->post('/xingzheng-panel/blast-message/send', $payload);
        $response->assertRedirect('/xingzheng-panel/blast-message');

        $blast = BlastMessage::latest()->first();
        $this->assertNotNull($blast);
        $this->assertEquals(3, $blast->total_recipients);
        $this->assertEquals(3, $blast->successful_count);
    }

    public function test_admin_can_send_test_broadcast(): void
    {
        Http::fake([
            'https://api.telegram.org/bot*/sendMessage' => Http::response([
                'ok' => true,
                'result' => ['message_id' => 5566],
            ], 200),
        ]);

        $payload = [
            'message' => 'Testing bot broadcast delivery channel.',
            'target_audience' => 'test',
            'test_chat_id' => '100000001',
        ];

        $response = $this->actingAs($this->admin)->post('/xingzheng-panel/blast-message/send', $payload);
        $response->assertRedirect('/xingzheng-panel/blast-message');

        $blast = BlastMessage::latest()->first();
        $this->assertNotNull($blast);
        $this->assertTrue($blast->is_test);
        $this->assertEquals(1, $blast->total_recipients);
        $this->assertEquals(1, $blast->successful_count);
    }

    public function test_broadcast_with_inline_button_and_photo(): void
    {
        Http::fake([
            'https://api.telegram.org/bot*/sendPhoto' => Http::response([
                'ok' => true,
                'result' => ['message_id' => 9991],
            ], 200),
        ]);

        $payload = [
            'title' => 'New Product Release with Banner',
            'message' => 'Check out our new web script release!',
            'target_audience' => 'members',
            'button_text' => '🚀 View Store',
            'button_url' => 'https://example.com/store',
            'image_url' => 'https://example.com/banner.png',
        ];

        $response = $this->actingAs($this->admin)->post('/xingzheng-panel/blast-message/send', $payload);
        $response->assertRedirect('/xingzheng-panel/blast-message');

        Http::assertSent(function (Request $request) {
            return str_contains($request->url(), 'sendPhoto')
                && $request['photo'] === 'https://example.com/banner.png'
                && isset($request['reply_markup']['inline_keyboard']);
        });

        $blast = BlastMessage::latest()->first();
        $this->assertEquals('https://example.com/banner.png', $blast->image_url);
        $this->assertEquals('🚀 View Store', $blast->button_text);
        $this->assertEquals('https://example.com/store', $blast->button_url);
    }

    public function test_broadcast_handles_failed_deliveries_gracefully(): void
    {
        // 1 user succeeds, 1 user blocked the bot (403)
        Http::fake([
            'https://api.telegram.org/bot*/sendMessage' => function (Request $request) {
                if ($request['chat_id'] == '200000002') {
                    return Http::response([
                        'ok' => false,
                        'error_code' => 403,
                        'description' => 'Forbidden: bot was blocked by the user',
                    ], 403);
                }

                return Http::response([
                    'ok' => true,
                    'result' => ['message_id' => 888],
                ], 200);
            },
        ]);

        $payload = [
            'message' => 'Broadcast with mixed success test.',
            'target_audience' => 'all',
        ];

        $response = $this->actingAs($this->admin)->post('/xingzheng-panel/blast-message/send', $payload);
        $response->assertRedirect('/xingzheng-panel/blast-message');

        $blast = BlastMessage::latest()->first();
        $this->assertEquals('partial', $blast->status);
        $this->assertEquals(2, $blast->total_recipients);
        $this->assertEquals(1, $blast->successful_count);
        $this->assertEquals(1, $blast->failed_count);

        // Check details array for error message
        $details = $blast->recipient_details;
        $failedItem = collect($details)->firstWhere('chat_id', '200000002');
        $this->assertNotNull($failedItem);
        $this->assertEquals('failed', $failedItem['status']);
        $this->assertStringContainsString('blocked', $failedItem['error']);
    }

    public function test_admin_can_view_blast_log_details(): void
    {
        $blast = BlastMessage::create([
            'user_id' => $this->admin->id,
            'title' => 'Test Campaign Log',
            'message' => 'Details modal content',
            'parse_mode' => 'Markdown',
            'target_audience' => 'all',
            'total_recipients' => 5,
            'successful_count' => 4,
            'failed_count' => 1,
            'status' => 'partial',
            'recipient_details' => [
                ['chat_id' => '111', 'name' => 'User 1', 'status' => 'sent', 'message_id' => 10],
                ['chat_id' => '222', 'name' => 'User 2', 'status' => 'failed', 'error' => 'Chat not found'],
            ],
        ]);

        $response = $this->actingAs($this->admin)->getJson("/xingzheng-panel/blast-message/{$blast->id}");
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'blast' => [
                'id' => $blast->id,
                'title' => 'Test Campaign Log',
                'successful_count' => 4,
                'failed_count' => 1,
            ],
        ]);
    }

    public function test_admin_can_delete_blast_history(): void
    {
        $blast = BlastMessage::create([
            'user_id' => $this->admin->id,
            'message' => 'Message to be deleted',
            'target_audience' => 'all',
            'total_recipients' => 1,
            'successful_count' => 1,
        ]);

        $response = $this->actingAs($this->admin)->delete("/xingzheng-panel/blast-message/{$blast->id}");
        $response->assertRedirect('/xingzheng-panel/blast-message');
        $this->assertDatabaseMissing('blast_messages', ['id' => $blast->id]);
    }

    public function test_broadcast_validation_requires_message_and_valid_audience(): void
    {
        $response = $this->actingAs($this->admin)->post('/xingzheng-panel/blast-message/send', [
            'message' => '',
            'target_audience' => 'invalid_target',
        ]);

        $response->assertSessionHasErrors(['message', 'target_audience']);
    }
}
