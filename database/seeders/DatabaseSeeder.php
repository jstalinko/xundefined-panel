<?php

namespace Database\Seeders;

use App\Models\Domain;
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
        $user = User::firstOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'Administrator',
                'password' => bcrypt('password'),
                'role' => 1,
                'invite_key' => 'XU-ROOT-7789'
            ]
        );

        if (Product::count() === 0) {
            $p1 = Product::create([
                'name' => 'X-Sentinel Threat Bot',
                'price' => 175000,
                'description' => 'Automated threat detection bot with Telegram telemetry, real-time intrusion monitoring, and anti-tamper heuristics.',
                'active' => true,
                'contents' => [
                    [
                        'file' => 'x-sentinel-v2.5.zip',
                        'version' => '2.5.0',
                        'changelog' => 'Added WebSocket live stream, enhanced packet capture filters.',
                        'md5sum' => 'a9f1b2c3d4e5f60718293a4b5c6d7e8f'
                    ],
                    [
                        'file' => 'x-sentinel-v2.4.zip',
                        'version' => '2.4.0',
                        'changelog' => 'Initial neural intrusion heuristics and telegram alerting engine.',
                        'md5sum' => '7e8f9a0b1c2d3e4f5a6b7c8d9e0f1a2b'
                    ],
                     [
                        'file' => 'x-sentinel-v2.3.zip',
                        'version' => '2.3.0',
                        'changelog' => 'Initial neural intrusion heuristics and telegram alerting engine.',
                        'md5sum' => '3135c34d6afc5ab25064416a56f981d2'
                     ],
                      [
                        'file' => 'x-sentinel-v2.1.zip',
                        'version' => '2.1.0',
                        'changelog' => 'Initial neural intrusion heuristics and telegram alerting engine.',
                        'md5sum' => '7e8f9a0b1c2d3e4f5a6b7c8d9e0f1a2b'
                    ]
                ]
            ]);

            $p2 = Product::create([
                'name' => 'Ghost Recon Framework',
                'price' => 250000,
                'description' => 'High-velocity OSINT reconnaissance engine, sub-domain brute-forcer, and DNS footprinting suite.',
                'active' => true,
                'contents' => [
                    [
                        'file' => 'ghost-recon-v1.8.2.zip',
                        'version' => '1.8.2',
                        'changelog' => 'Updated CIDR range scanner and ASN lookup tables.',
                        'md5sum' => '4d5e6f7a8b9c0d1e2f3a4b5c6d7e8f9a'
                    ],
                    [
                        'file' => 'ghost-recon-v1.8.0.zip',
                        'version' => '1.8.0',
                        'changelog' => 'Core DNS footprinting and subdomain wordlist brute-forcer.',
                        'md5sum' => '3c2b1a0f9e8d7c6b5a4f3e2d1c0b9a8f'
                    ]
                ]
            ]);

            $p3 = Product::create([
                'name' => 'Cipher Vault Kernel v3',
                'price' => 320000,
                'description' => 'Cryptographic token manager, salted payload validator, and AES-256-GCM hardware key integration tool.',
                'active' => true,
                'contents' => [
                    [
                        'file' => 'cipher-vault-v3.0.1.zip',
                        'version' => '3.0.1',
                        'changelog' => 'Implemented SHA-512 streaming hash and PKCS#11 key storage driver.',
                        'md5sum' => '9a8b7c6d5e4f3a2b1c0d9e8f7a6b5c4d'
                    ],
                    [
                        'file' => 'cipher-vault-v3.0.0.zip',
                        'version' => '3.0.0',
                        'changelog' => 'Initial kernel architecture and AES-256 encryption engine.',
                        'md5sum' => '5d4c3b2a1f0e9d8c7b6a5f4e3d2c1b0a'
                    ]
                ]
            ]);

            // Seed a completed order for admin on product 1
            Order::create([
                'invoice' => 'INV-XU8821-' . date('ymd'),
                'user_id' => $user->id,
                'product_id' => $p1->id,
                'price' => $p1->price,
                'payment_method' => 'CyberPay Instant Gateway',
                'status' => 'completed',
            ]);

            // Seed sample domain
            Domain::create([
                'user_id' => $user->id,
                'product_id' => $p1->id,
                'domain' => 'sentinel.xundefined.io',
            ]);
        }

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
                'content' => "Connecting your own domain or subdomain to your products is simple:\n\n1. Open the xDomain menu in your dashboard.\n2. Click 'Register Domain' and enter your domain hostname (e.g. app.yourdomain.com).\n3. Assign the domain to your active product.\n4. Point your domain's DNS CNAME/A record to our server IP.\n5. Click 'Ping' to verify DNS status.",
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
    }
}

