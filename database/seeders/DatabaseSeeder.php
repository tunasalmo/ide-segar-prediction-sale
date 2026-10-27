<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /** Kode mnemonic per kategori agar kode produk stabil dan mudah dibaca. */
    private const MNEMONIC = [
        'SPANDUK' => 'SPD',
        'BUKU' => 'BKQ',
        'Manual / Jasa' => 'MJS',
        'BOX' => 'BOX',
        'BENDERA' => 'BND',
        'KEMEJA OUTDOOR' => 'KMO',
        'SERAGAM OLAHRAGA' => 'SOL',
        'KAOS' => 'KSO',
        'BUKU NOTA' => 'BNT',
        'SABLON' => 'SBN',
        'JERSEY' => 'JRS',
        'GOODIE BAG' => 'GDB',
        'STIKER' => 'STK',
        'STAMPEL' => 'SMP',
        'SUBLIM' => 'SBM',
        'X-BANNER' => 'XBN',
        'STREET BANNER' => 'STR',
        'KARTU NAMA' => 'KRN',
        'LASER PRINT' => 'LPR',
    ];

    private const CATEGORY_COLORS = [
        'SPANDUK' => '#5442C9',
        'BUKU' => '#2E9BD6',
        'Manual / Jasa' => '#E0448E',
        'BOX' => '#F2B705',
        'BENDERA' => '#0F9D58',
        'KEMEJA OUTDOOR' => '#E8820C',
        'SERAGAM OLAHRAGA' => '#0F766E',
        'KAOS' => '#D62839',
        'BUKU NOTA' => '#8A5CF6',
        'SABLON' => '#4B5563',
        'JERSEY' => '#0EA5E9',
        'GOODIE BAG' => '#B45309',
        'STIKER' => '#16A34A',
        'STAMPEL' => '#64748B',
        'SUBLIM' => '#7C3AED',
        'X-BANNER' => '#2563EB',
        'STREET BANNER' => '#0891B2',
        'KARTU NAMA' => '#DC2626',
        'LASER PRINT' => '#374151',
    ];

    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        OrderItem::query()->delete();
        Order::query()->delete();
        Customer::query()->delete();
        Product::query()->delete();
        Category::query()->delete();
        foreach (['order_items', 'orders', 'customers', 'products', 'categories'] as $table) {
            DB::statement("ALTER TABLE {$table} AUTO_INCREMENT = 1");
        }
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $catalog = json_decode(file_get_contents(database_path('seed-data/catalog.json')), true);
        $sales = json_decode(file_get_contents(database_path('seed-data/sales.json')), true);

        $this->importCatalog($catalog);
        $this->importSales($sales);

        if (User::query()->count() === 0) {
            User::create([
                'name' => 'Admin IDE Segar',
                'email' => 'admin@idesegarprinting.id',
                'password' => 'password',
            ]);
        }
    }

    private function importCatalog(array $catalog): void
    {
        $seq = [];
        foreach ($catalog['categories'] as $catName) {
            Category::create([
                'name' => $catName,
                'slug' => str()->slug($catName),
                'color' => self::CATEGORY_COLORS[$catName] ?? '#5442C9',
                'description' => null,
                'is_active' => true,
            ]);
        }

        foreach ($catalog['products'] as $p) {
            $cat = Category::where('name', $p['category'])->first();
            if (! $cat) {
                continue;
            }
            $mnemonic = self::MNEMONIC[$cat->name] ?? strtoupper(str()->substr(str()->slug($cat->name), 0, 3));
            $seq[$mnemonic] = ($seq[$mnemonic] ?? 0) + 1;
            $code = sprintf('%s-%03d', $mnemonic, $seq[$mnemonic]);

            Product::create([
                'category_id' => $cat->id,
                'code' => $code,
                'name' => $p['name'],
                'unit' => strtolower($p['unit']),
                'price' => $p['price'],
                'stock' => null, // data stok belum tersedia; tidak mengarang angka
                'is_active' => true,
            ]);
        }
    }

    private function importSales(array $sales): void
    {
        $customers = [];
        $productsByName = Product::with('category')->get()->keyBy(function ($p) {
            return strtolower($p->category->name.'|'.$p->name);
        });

        foreach ($sales['orders'] as $o) {
            $custName = trim($o['customer']);
            $custKey = mb_strtolower($custName);
            if (! isset($customers[$custKey])) {
                $customers[$custKey] = Customer::create(['name' => $custName]);
            }

            $payment = match ($o['payment_status']) {
                'Lunas' => 'lunas',
                'DP' => 'dp',
                default => 'belum_bayar',
            };
            $status = match ($payment) {
                'lunas' => 'selesai',
                'dp' => 'diproses',
                default => 'menunggu_konfirmasi',
            };

            $order = Order::create([
                'invoice_no' => $o['invoice_no'],
                'customer_id' => $customers[$custKey]->id,
                'order_date' => $o['date'],
                'status' => $status,
                'payment_status' => $payment,
                'total' => $o['total'],
            ]);

            foreach ($o['items'] as $item) {
                $key = strtolower($item['category'].'|'.$item['product']);
                $product = $productsByName[$key] ?? null;

                $order->items()->create([
                    'product_id' => $product?->id,
                    'description' => $item['product'].($item['note'][0] ? ' — '.$item['note'][0] : ''),
                    'unit' => $item['unit'],
                    'qty' => $item['qty'],
                    'unit_price' => $item['unit_price'],
                    'total' => $item['total'],
                ]);
            }
        }
    }
}
