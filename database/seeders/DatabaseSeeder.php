<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with initial production-ready dataset.
     */
    public function run(): void
    {
        // 1. Seed Administrator and Staff Users
        $admin = User::firstOrCreate(
            ['email' => 'admin@admintopi.com'],
            [
                'name' => 'Administrator Topi',
                'password' => Hash::make('Password123!'),
                'role' => 'admin',
            ]
        );

        $staff = User::firstOrCreate(
            ['email' => 'staff@admintopi.com'],
            [
                'name' => 'Staff Inventory',
                'password' => Hash::make('Password123!'),
                'role' => 'staff',
            ]
        );

        // 2. Seed Hat Categories
        $categoriesData = [
            [
                'name' => 'Snapback',
                'slug' => 'snapback',
                'description' => 'Topi dengan visor datar dan snap pengatur ukuran di bagian belakang, populer dalam gaya streetwear.',
                'is_active' => true,
            ],
            [
                'name' => 'Baseball Cap',
                'slug' => 'baseball-cap',
                'description' => 'Topi santai klasik dengan visor melengkung yang nyaman dipakai untuk kegiatan luar ruangan.',
                'is_active' => true,
            ],
            [
                'name' => 'Bucket Hat',
                'slug' => 'bucket-hat',
                'description' => 'Topi santai dengan tepi melingkar ke bawah, memberikan perlindungan ekstra dari terik sinar matahari.',
                'is_active' => true,
            ],
            [
                'name' => 'Beanie',
                'slug' => 'beanie',
                'description' => 'Topi rajut fleksibel tanpa visor untuk gaya santai dan menghangatkan kepala saat cuaca dingin.',
                'is_active' => true,
            ],
            [
                'name' => 'Trucker Hat',
                'slug' => 'trucker-hat',
                'description' => 'Topi dengan bagian depan busa kokoh dan panel jaring sirkulasi udara di bagian samping dan belakang.',
                'is_active' => true,
            ],
            [
                'name' => 'Fedora',
                'category' => 'Fedora',
                'slug' => 'fedora',
                'description' => 'Topi gaya semi-formal vintage dengan lekukan di bagian atas mahkota dan pinggiran terstruktur.',
                'is_active' => true,
            ],
            [
                'name' => 'Novel & Fiksi',
                'category' => 'Novel & Fiksi',
                'slug' => 'novel-fiksi',
                'description' => 'Koleksi novel sastra, roman, petualangan, dan fiksi kontemporer terbaik.',
                'is_active' => true,
            ],
            [
                'name' => 'Pengembangan Diri',
                'category' => 'Pengembangan Diri',
                'slug' => 'pengembangan-diri',
                'description' => 'Buku motivasi, produktivitas, psikologi populer, dan pengembangan karir profesional.',
                'is_active' => true,
            ],
            [
                'name' => 'Sains & Teknologi',
                'category' => 'Sains & Teknologi',
                'slug' => 'sains-teknologi',
                'description' => 'Buku komputer, pemrograman, sains terapan, dan referensi teknologi informasi.',
                'is_active' => true,
            ],
            [
                'name' => 'Komik & Manga',
                'category' => 'Komik & Manga',
                'slug' => 'komik-manga',
                'description' => 'Koleksi komik grafis, manga Jepang, dan serial komik populer anak muda.',
                'is_active' => true,
            ],
        ];

        $categoryModels = [];
        foreach ($categoriesData as $data) {
            $categoryModels[$data['slug']] = Category::firstOrCreate(['slug' => $data['slug']], $data);
        }

        // 3. Seed Products with initial stock and audit movements
        $productsData = [
            [
                'category_slug' => 'snapback',
                'name' => 'Urban Street Flat Snapback Black',
                'sku' => 'TOPI-SNP-001',
                'description' => 'Snapback premium warna hitam pekat berbahan cotton twill tebal dengan bordir timbul 3D di bagian depan.',
                'price' => 149000.00,
                'stock' => 50,
                'material' => 'Cotton Twill Heavyweight',
                'status' => 'published',
            ],
            [
                'category_slug' => 'snapback',
                'name' => 'Retro Vintage Two-Tone Snapback Maroon',
                'sku' => 'TOPI-SNP-002',
                'description' => 'Topi snapback kombinasi warna merah marun dan krem dengan buckle premium di bagian penutup belakang.',
                'price' => 165000.00,
                'stock' => 35,
                'material' => 'Wool Blend & Canvas',
                'status' => 'published',
            ],
            [
                'category_slug' => 'baseball-cap',
                'name' => 'Dad Hat Classic Curve Navy',
                'sku' => 'TOPI-BSC-001',
                'description' => 'Baseball cap unconstructed dengan strap logam kuningan antik, sangat nyaman dan ringan untuk pemakaian harian.',
                'price' => 125000.00,
                'stock' => 80,
                'material' => 'Washed Chino Cotton',
                'status' => 'published',
            ],
            [
                'category_slug' => 'baseball-cap',
                'name' => 'Athletic Performance Dri-Fit Cap White',
                'sku' => 'TOPI-BSC-002',
                'description' => 'Topi olahraga cepat kering dengan lubang ventilasi laser-cut untuk sirkulasi udara maksimal.',
                'price' => 179000.00,
                'stock' => 0, // Out of stock example for filter testing
                'material' => 'Polyester Quickdry Spandex',
                'status' => 'published',
            ],
            [
                'category_slug' => 'bucket-hat',
                'name' => 'Reversible Safari Bucket Hat Olive & Beige',
                'sku' => 'TOPI-BKT-001',
                'description' => 'Bucket hat 2-in-1 yang dapat dibalik dengan dua pilihan warna berbeda, tahan percikan air ringan.',
                'price' => 139000.00,
                'stock' => 45,
                'material' => 'Ripstop Water-repellent Cotton',
                'status' => 'published',
            ],
            [
                'category_slug' => 'beanie',
                'name' => 'Ribbed Fisherman Beanie Charcoal',
                'sku' => 'TOPI-BNE-001',
                'description' => 'Beanie rajut motif rusuk elastis warna abu-abu arang dengan lipatan pendek khas pelaut.',
                'price' => 99000.00,
                'stock' => 60,
                'material' => 'Acrylic Wool Knit',
                'status' => 'published',
            ],
            [
                'category_slug' => 'trucker-hat',
                'name' => 'Heritage Mesh Trucker Cap Forest Green',
                'sku' => 'TOPI-TRK-001',
                'description' => 'Trucker hat vintage dengan jaring breathability tinggi dan patch kulit sintetis embossed di bagian depan.',
                'price' => 119000.00,
                'stock' => 40,
                'material' => 'Poly-Foam & Breathable Mesh',
                'status' => 'published',
            ],
            [
                'category_slug' => 'fedora',
                'name' => 'Classic Wool Felt Wide-Brim Fedora Brown',
                'sku' => 'TOPI-FDR-001',
                'description' => 'Topi fedora elegan berbahan felt wol murni dengan pita grosgrain kontras untuk acara formal maupun semi-kasual.',
                'price' => 285000.00,
                'stock' => 15,
                'material' => '100% Australian Wool Felt',
                'status' => 'draft',
            ],
            [
                'category_slug' => 'novel-fiksi',
                'name' => 'Laut Bercerita - Leila S. Chudori',
                'sku' => 'BK-NOVEL-001',
                'description' => 'Novel bertema persahabatan, cinta, dan kehilangan dengan latar belakang sejarah reformasi 1998 di Indonesia.',
                'price' => 115000.00,
                'stock' => 45,
                'material' => 'Bookpaper Softcover',
                'status' => 'published',
            ],
            [
                'category_slug' => 'novel-fiksi',
                'name' => 'Bumi Manusia - Pramoedya Ananta Toer',
                'sku' => 'BK-NOVEL-002',
                'description' => 'Mahakarya sastra Indonesia Tetralogi Buru mengisahkan perjalanan Minke di era pergolakan kolonialisme Hindia Belanda.',
                'price' => 135000.00,
                'stock' => 30,
                'material' => 'Bookpaper Eksklusif',
                'status' => 'published',
            ],
            [
                'category_slug' => 'pengembangan-diri',
                'name' => 'Atomic Habits - James Clear (Edisi Bahasa Indonesia)',
                'sku' => 'BK-SLF-001',
                'description' => 'Panduan terbukti untuk membangun kebiasaan baik dan menghentikan kebiasaan buruk melalui perubahan 1 persen setiap hari.',
                'price' => 108000.00,
                'stock' => 65,
                'material' => 'Bookpaper Softcover',
                'status' => 'published',
            ],
            [
                'category_slug' => 'pengembangan-diri',
                'name' => 'Filosofi Teras - Henry Manampiring',
                'sku' => 'BK-SLF-002',
                'description' => 'Penerapan filsafat Stoisisme kuno Yunani-Romawi untuk mengatasi kecemasan dan stres mental dalam kehidupan modern.',
                'price' => 98000.00,
                'stock' => 50,
                'material' => 'Bookpaper Softcover',
                'status' => 'published',
            ],
            [
                'category_slug' => 'sains-teknologi',
                'name' => 'Clean Code: A Handbook of Agile Software Craftsmanship',
                'sku' => 'BK-TEK-001',
                'description' => 'Buku pedoman klasik pengembangan perangkat lunak profesional oleh Robert C. Martin (Uncle Bob).',
                'price' => 295000.00,
                'stock' => 20,
                'material' => 'Hardcover Import Paper',
                'status' => 'published',
            ],
            [
                'category_slug' => 'komik-manga',
                'name' => 'Detektif Conan Edisi Spesial Vol. 100',
                'sku' => 'BK-KMK-001',
                'description' => 'Volume bersejarah petualangan Shinichi Kudo dan organisasi jubah hitam karya komikus legendaris Gosho Aoyama.',
                'price' => 45000.00,
                'stock' => 75,
                'material' => 'HVS Premium Graphic Comic Paper',
                'status' => 'published',
            ],
        ];

        foreach ($productsData as $item) {
            $category = $categoryModels[$item['category_slug']];

            $product = Product::firstOrCreate(
                ['sku' => $item['sku']],
                [
                    'category_id' => $category->id,
                    'user_id' => $admin->id,
                    'name' => $item['name'],
                    'slug' => Str::slug($item['name']) . '-' . Str::lower(Str::random(5)),
                    'description' => $item['description'],
                    'price' => $item['price'],
                    'stock' => $item['stock'],
                    'material' => $item['material'],
                    'status' => $item['status'],
                ]
            );

            if ($product->wasRecentlyCreated && $product->stock > 0) {
                StockMovement::create([
                    'product_id' => $product->id,
                    'user_id' => $admin->id,
                    'type' => 'in',
                    'quantity' => $product->stock,
                    'previous_stock' => 0,
                    'current_stock' => $product->stock,
                    'notes' => 'Inisialisasi stok awal dari database seeder.',
                ]);
            }
        }
    }
}
