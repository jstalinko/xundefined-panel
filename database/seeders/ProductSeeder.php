<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            [
                'name' => 'X-Sentinel Threat Bot',
                'slug' => 'x-sentinel-threat-bot',
                'pid' => 'PID-SENTINEL-01',
                'category' => 'Security & Threat Bot',
                'price' => 45.00,
                'description' => 'Automated threat detection bot with Telegram telemetry, real-time intrusion monitoring, and anti-tamper heuristics.',
                'status' => 'active',
                'active' => true,
                'published' => true,
                'contents' => [
                    [
                        'file' => 'x-sentinel-v2.5.0.zip',
                        'version' => '2.5.0',
                        'changelog' => 'Added WebSocket live stream, enhanced packet capture filters.',
                        'md5checksum' => 'c99c3b634ef19ce88a9eb4539ca4bab5',
                        'md5sum' => 'c99c3b634ef19ce88a9eb4539ca4bab5'
                    ],
                    [
                        'file' => 'x-sentinel-v2.4.0.zip',
                        'version' => '2.4.0',
                        'changelog' => 'Initial neural intrusion heuristics and telegram alerting engine.',
                        'md5checksum' => '6c08466345fcf0c431212c1bf145f2ad',
                        'md5sum' => '6c08466345fcf0c431212c1bf145f2ad'
                    ],
                    [
                        'file' => 'x-sentinel-v2.3.0.zip',
                        'version' => '2.3.0',
                        'changelog' => 'Enhanced telemetry payload formatter.',
                        'md5checksum' => 'bfe322aef8dff646e54801e08d0a3a62',
                        'md5sum' => 'bfe322aef8dff646e54801e08d0a3a62'
                    ]
                ],
            ],
            [
                'name' => 'Ghost Recon Framework',
                'slug' => 'ghost-recon-framework',
                'pid' => 'PID-GHOST-02',
                'category' => 'OSINT & Recon Framework',
                'price' => 65.00,
                'description' => 'High-velocity OSINT reconnaissance engine, sub-domain brute-forcer, and DNS footprinting suite.',
                'status' => 'active',
                'active' => true,
                'published' => true,
                'contents' => [
                    [
                        'file' => 'ghost-recon-v1.8.2.zip',
                        'version' => '1.8.2',
                        'changelog' => 'Updated CIDR range scanner and ASN lookup tables.',
                        'md5checksum' => '08cb3f782a184e42bb62b4e4c7ef0513',
                        'md5sum' => '08cb3f782a184e42bb62b4e4c7ef0513'
                    ],
                    [
                        'file' => 'ghost-recon-v1.8.0.zip',
                        'version' => '1.8.0',
                        'changelog' => 'Core DNS footprinting and subdomain wordlist brute-forcer.',
                        'md5checksum' => '21d9dd27744af42c0542efd0b4b5349c',
                        'md5sum' => '21d9dd27744af42c0542efd0b4b5349c'
                    ]
                ],
            ],
            [
                'name' => 'Cipher Vault Kernel v3',
                'slug' => 'cipher-vault-kernel-v3',
                'pid' => 'PID-CIPHER-03',
                'category' => 'Cryptography & Key Vault',
                'price' => 85.00,
                'description' => 'Cryptographic token manager, salted payload validator, and AES-256-GCM hardware key integration tool.',
                'status' => 'active',
                'active' => true,
                'published' => true,
                'contents' => [
                    [
                        'file' => 'cipher-vault-v3.0.1.zip',
                        'version' => '3.0.1',
                        'changelog' => 'Implemented SHA-512 streaming hash and PKCS#11 key storage driver.',
                        'md5checksum' => '7530c4a917d9abdc74d1ad49f2a98f1f',
                        'md5sum' => '7530c4a917d9abdc74d1ad49f2a98f1f'
                    ],
                    [
                        'file' => 'cipher-vault-v3.0.0.zip',
                        'version' => '3.0.0',
                        'changelog' => 'Initial kernel architecture and AES-256 encryption engine.',
                        'md5checksum' => '601a087239a324bc49d84c5f329a5955',
                        'md5sum' => '601a087239a324bc49d84c5f329a5955'
                    ]
                ],
            ],
        ];

        // Clean up any other products so only 3 clean products exist
        Product::whereNotIn('slug', array_column($products, 'slug'))->delete();

        foreach ($products as $prod) {
            Product::updateOrCreate(
                ['slug' => $prod['slug']],
                $prod
            );
        }
    }
}
