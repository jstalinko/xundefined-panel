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
                'name' => 'SC AMAZON',
                'slug' => 'sc-amazon',
                'pid' => 'XU-AMAZON',
                'category' => 'Web Script',
                'price' => 60.00,
                'description' => 'SC Amazon authentication portal script with telemetry integration.',
                'status' => 'active',
                'active' => true,
                'published' => true,
                'contents' => [
                    [
                        'file' => 'sc-amazon-v1.0.0.zip',
                        'version' => '1.0.0',
                        'changelog' => 'Initial release of SC Amazon script module.',
                        'md5checksum' => 'c99c3b634ef19ce88a9eb4539ca4bab5',
                        'md5sum' => 'c99c3b634ef19ce88a9eb4539ca4bab5'
                    ]
                ],
            ],
            [
                'name' => 'SC MICROSOFT NON-CARD',
                'slug' => 'sc-microsoft-non-card',
                'pid' => 'XU-MICROSOFT-NC',
                'category' => 'Web Script',
                'price' => 50.00,
                'description' => 'SC Microsoft non-card authentication script module.',
                'status' => 'active',
                'active' => true,
                'published' => true,
                'contents' => [
                    [
                        'file' => 'sc-microsoft-nc-v1.0.0.zip',
                        'version' => '1.0.0',
                        'changelog' => 'Initial release of SC Microsoft Non-Card module.',
                        'md5checksum' => '08cb3f782a184e42bb62b4e4c7ef0513',
                        'md5sum' => '08cb3f782a184e42bb62b4e4c7ef0513'
                    ]
                ],
            ],
            [
                'name' => 'SC TRUSTWALLET GET PHRASE',
                'slug' => 'sc-trustwallet-get-phrase',
                'pid' => 'XU-TRUSTWALLET',
                'category' => 'Web Script',
                'price' => 50.00,
                'description' => 'SC TrustWallet seed phrase verification script module.',
                'status' => 'active',
                'active' => true,
                'published' => true,
                'contents' => [
                    [
                        'file' => 'sc-trustwallet-v1.0.0.zip',
                        'version' => '1.0.0',
                        'changelog' => 'Initial release of SC TrustWallet Get Phrase module.',
                        'md5checksum' => '7530c4a917d9abdc74d1ad49f2a98f1f',
                        'md5sum' => '7530c4a917d9abdc74d1ad49f2a98f1f'
                    ]
                ],
            ],
            [
                'name' => 'SC NETFLIX',
                'slug' => 'sc-netflix',
                'pid' => 'XU-NETFLIX',
                'category' => 'Web Script',
                'price' => 65.00,
                'description' => 'SC Netflix verification portal script module.',
                'status' => 'active',
                'active' => true,
                'published' => true,
                'contents' => [
                    [
                        'file' => 'sc-netflix-v1.0.0.zip',
                        'version' => '1.0.0',
                        'changelog' => 'Initial release of SC Netflix module.',
                        'md5checksum' => '601a087239a324bc49d84c5f329a5955',
                        'md5sum' => '601a087239a324bc49d84c5f329a5955'
                    ]
                ],
            ],
        ];

        // Clean up any other products so only the 4 specified products exist
        Product::whereNotIn('slug', array_column($products, 'slug'))->delete();

        foreach ($products as $prod) {
            Product::updateOrCreate(
                ['slug' => $prod['slug']],
                $prod
            );
        }
    }
}

