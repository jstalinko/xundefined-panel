<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\ActivityLog;
use App\Models\Domain;
use App\Models\Invitecode;
use App\Models\Order;
use App\Models\Post;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Seed Admin User
        $user = User::updateOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'Administrator',
                'password' => bcrypt('password'),
                'role' => 'admin',
                'account_key' => 'XU-ROOT-7789',
                'telegram_id' => '123456789',
                'telegram_username' => 'admin',
                'balance' => 1000.00,
            ]
        );

        // 2. Seed Products Catalog
        $this->call(ProductSeeder::class);

        $p1 = Product::where('slug', 'x-sentinel-threat-bot')->first() ?? Product::first();
        $p2 = Product::where('slug', 'ghost-recon-framework')->first();
        $p3 = Product::where('slug', 'cipher-vault-kernel-v3')->first();

        // 3. Seed Sample Order
        if ($p1 && Order::where('user_id', $user->id)->where('product_id', $p1->id)->count() === 0) {
            $invoice = 'INV-XU8821-' . date('ymd');
            $orderNumber = 'ORD-' . date('Ymd') . '-XU8821';

            Order::create([
                'order_number' => $orderNumber,
                'invoice' => $invoice,
                'user_id' => $user->id,
                'product_id' => $p1->id,
                'amount' => $p1->price,
                'price' => $p1->price,
                'domain_quota' => 3,
                'payment_method' => 'CyberPay Instant Gateway',
                'status' => 'completed',
                'download_token' => 'dl_master_token_xsentinel',
                'notes' => 'Seeded master administrative order',
            ]);
        }

        // 4. Seed Sample Domain
        if ($p1 && Domain::where('domain', 'sentinel.xundefined.io')->count() === 0) {
            Domain::create([
                'user_id' => $user->id,
                'product_id' => $p1->id,
                'domain' => 'sentinel.xundefined.io',
                'status' => 'active',
                'hits' => 42,
            ]);
        }

        // 5. Seed Posts / Notes
        if (Post::count() === 0) {
            Post::create([
                'slug' => 'welcome-to-xundefined-v2-platform',
                'title' => 'Welcome to xUndefined Platform v2.0',
                'category' => 'announcement',
                'content' => "We are excited to launch the xUndefined Platform v2.0! This release introduces a streamlined dashboard, unified product catalog, multiple version download support with SHA/MD5 checksum verification, and fast custom domain management.\n\nAll operatives and developers can now access their tools and manage connected endpoints with ease.",
                'image' => '/no-image.svg',
                'is_published' => true,
            ]);

            Post::create([
                'slug' => 'x-sentinel-v2-5-release-notes',
                'title' => 'X-Sentinel Threat Bot v2.5.0 Released',
                'category' => 'changelog',
                'content' => "X-Sentinel Threat Bot version 2.5.0 is now live in your Download Vault.\n\nKey Changes:\n- Added real-time WebSocket telemetry stream.\n- Enhanced packet capture filters.\n- Improved Telegram alerting heuristics.\n- Optimized memory consumption under high payload throughput.",
                'image' => '/no-image.svg',
                'is_published' => true,
            ]);

            Post::create([
                'slug' => 'guide-binding-custom-domains-to-products',
                'title' => 'Guide: Connecting Custom Domains to Your Products',
                'category' => 'tutorial',
                'content' => "Connecting your own domain or subdomain to your products is simple:\n\n1. Open the xDomain menu in your dashboard or Telegram bot.\n2. Enter your domain hostname (e.g. app.yourdomain.com).\n3. Assign the domain to your active product.\n4. Point your domain's DNS CNAME/A record to our server IP.\n5. Click 'Ping' to verify DNS status.",
                'image' => '/no-image.svg',
                'is_published' => true,
            ]);

            Post::create([
                'slug' => 'scheduled-maintenance-and-security-upgrade',
                'title' => 'System Maintenance & Infrastructure Upgrade',
                'category' => 'news',
                'content' => "Our team will be performing scheduled security and network optimization on Saturday between 02:00 UTC and 03:00 UTC. Download vaults and API endpoints will remain fully operational during this window.",
                'image' => '/no-image.svg',
                'is_published' => true,
            ]);
        }

        // 6. Seed Invite Codes
        if (Invitecode::count() === 0) {
            Invitecode::create([
                'code' => 'XU-ROOT-7789',
                'expired_at' => now()->addYear(),
                'used_at' => now(),
                'used' => true,
                'used_by_user_id' => $user->id,
                'generate_via' => 'system_seed',
                'products_id' => array_values(array_filter([$p1?->id, $p2?->id, $p3?->id])),
            ]);

            Invitecode::create([
                'code' => 'XU-OPERATIVE-2026',
                'expired_at' => now()->addMonths(6),
                'used' => false,
                'generate_via' => 'admin',
                'products_id' => array_values(array_filter([$p1?->id])),
            ]);
        }

        // 7. Seed Activities & Logs
        if (Activity::where('user_id', $user->id)->count() === 0) {
            Activity::create([
                'user_id' => $user->id,
                'action' => 'SYSTEM_INIT',
                'description' => 'System seeded and ready for operations.',
                'properties' => ['version' => '2.0.0'],
            ]);
        }

        if (ActivityLog::where('user_id', $user->id)->count() === 0) {
            ActivityLog::create([
                'user_id' => $user->id,
                'type' => 'system',
                'description' => 'System seeded and ready for operations.',
            ]);
        }
    }
}
