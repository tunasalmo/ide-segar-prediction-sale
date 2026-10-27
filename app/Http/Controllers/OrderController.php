<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Order::with(['customer', 'items']);

        if ($q = $request->string('q')->trim()->toString()) {
            $query->search($q);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }
        if ($request->filled('payment')) {
            $query->where('payment_status', $request->string('payment')->toString());
        }
        if ($request->filled('from')) {
            $query->whereDate('order_date', '>=', $request->string('from')->toString());
        }
        if ($request->filled('to')) {
            $query->whereDate('order_date', '<=', $request->string('to')->toString());
        }

        $orders = $query->orderByDesc('order_date')
            ->paginate(15)
            ->withQueryString();

        $orders->getCollection()->transform(function (Order $o) {
            return [
                'id' => $o->id,
                'invoice_no' => $o->invoice_no,
                'customer_id' => $o->customer_id,
                'customer' => $o->customer->name,
                'order_date' => $o->order_date->format('d M Y'),
                'order_date_iso' => $o->order_date->format('Y-m-d'),
                'notes' => $o->notes,
                'total' => (float) $o->total,
                'status' => $o->status,
                'status_label' => $o->status_label,
                'payment_status' => $o->payment_status,
                'payment_label' => $o->payment_label,
                'items_count' => $o->items->count(),
                'items' => $o->items->map(fn ($i) => [
                    'id' => $i->id,
                    'product_id' => $i->product_id,
                    'description' => $i->description,
                    'unit' => $i->unit,
                    'qty' => (float) $i->qty,
                    'unit_price' => (float) $i->unit_price,
                    'total' => (float) $i->total,
                ]),
            ];
        });

        return Inertia::render('Order/Index', [
            'orders' => $orders,
            'filters' => [
                'q' => $request->string('q')->toString(),
                'status' => $request->string('status')->toString(),
                'payment' => $request->string('payment')->toString(),
                'from' => $request->string('from')->toString(),
                'to' => $request->string('to')->toString(),
            ],
            'counts' => [
                'menunggu_konfirmasi' => Order::where('status', 'menunggu_konfirmasi')->count(),
                'diproses' => Order::where('status', 'diproses')->count(),
                'selesai' => Order::where('status', 'selesai')->count(),
                'belum_bayar' => Order::where('payment_status', 'belum_bayar')->count(),
                'dp' => Order::where('payment_status', 'dp')->count(),
            ],
            'customers' => Customer::orderBy('name')->get(['id', 'name']),
            'products' => Product::orderBy('name')->get(['id', 'code', 'name', 'unit', 'price'])
                ->map(fn ($p) => [
                    'id' => $p->id,
                    'code' => $p->code,
                    'name' => $p->name,
                    'unit' => $p->unit,
                    'price' => (float) $p->price,
                ]),
        ]);
    }

    public function show(Request $request, Order $order): Response
    {
        $order->load('customer', 'items.product');

        return Inertia::render('Order/Show', [
            'order' => [
                'id' => $order->id,
                'invoice_no' => $order->invoice_no,
                'order_date' => $order->order_date->format('d M Y'),
                'status' => $order->status,
                'status_label' => $order->status_label,
                'payment_status' => $order->payment_status,
                'payment_label' => $order->payment_label,
                'total' => (float) $order->total,
                'notes' => $order->notes,
                'created_at' => $order->created_at->format('d M Y H:i'),
                'customer' => [
                    'id' => $order->customer->id,
                    'name' => $order->customer->name,
                    'phone' => $order->customer->phone,
                ],
                'items' => $order->items->map(fn ($i) => [
                    'id' => $i->id,
                    'description' => $i->description,
                    'product_id' => $i->product_id,
                    'code' => $i->product?->code,
                    'unit' => $i->unit,
                    'qty' => (float) $i->qty,
                    'unit_price' => (float) $i->unit_price,
                    'total' => (float) $i->total,
                ]),
            ],
            'statuses' => Order::STATUSES,
            'paymentStatuses' => Order::PAYMENT_STATUSES,
        ]);
    }

    public function updateStatus(Request $request, Order $order)
    {
        $data = $this->validate($request, [
            'status' => ['required', Rule::in(array_keys(Order::STATUSES))],
            'payment_status' => ['required', Rule::in(array_keys(Order::PAYMENT_STATUSES))],
        ]);

        $order->update($data);

        if ($data['status'] === 'menunggu_konfirmasi' || $data['status'] === 'diproses') {
            AppNotification::create([
                'user_id' => $request->user()->id,
                'type' => 'order',
                'title' => 'Status pesanan diperbarui',
                'body' => $order->invoice_no.' ('.$order->customer->name.') → '.Order::STATUSES[$data['status']].'.',
                'link' => '/pesanan/'.$order->id,
            ]);
        }

        return back()->with('success', 'Status '.$order->invoice_no.' diperbarui.');
    }

    /**
     * Alur flowchart langkah 4: "Kelola data penjualan historis" — tambah.
     */
    public function store(Request $request)
    {
        $data = $this->validateOrder($request);

        return DB::transaction(function () use ($data) {
            $customer = $this->resolveCustomer($data);
            $order = Order::create([
                'invoice_no' => $data['invoice_no'] ?? $this->generateInvoiceNo($customer, $data['order_date']),
                'customer_id' => $customer->id,
                'order_date' => $data['order_date'],
                'status' => $data['status'],
                'payment_status' => $data['payment_status'],
                'total' => 0,
                'notes' => $data['notes'] ?? null,
            ]);
            $total = $this->syncItems($order, $data['items']);
            $order->update(['total' => $total]);

            return back()->with('success', 'Data penjualan '.$order->invoice_no.' ditambahkan.');
        });
    }

    /**
     * Alur flowchart langkah 4: "Kelola data penjualan historis" — ubah.
     */
    public function update(Request $request, Order $order)
    {
        $data = $this->validateOrder($request, $order);

        return DB::transaction(function () use ($data, $order) {
            $customer = $this->resolveCustomer($data);
            $order->update([
                'invoice_no' => $data['invoice_no'] ?? $order->invoice_no,
                'customer_id' => $customer->id,
                'order_date' => $data['order_date'],
                'status' => $data['status'],
                'payment_status' => $data['payment_status'],
                'notes' => $data['notes'] ?? null,
            ]);
            $order->items()->delete();
            $total = $this->syncItems($order, $data['items']);
            $order->update(['total' => $total]);

            return back()->with('success', 'Data penjualan '.$order->invoice_no.' diperbarui.');
        });
    }

    /**
     * Alur flowchart langkah 4: "Kelola data penjualan historis" — hapus.
     */
    public function destroy(Order $order)
    {
        $invoice = $order->invoice_no;
        $order->delete(); // order_items ikut terhapus (cascade)

        return back()->with('success', 'Data penjualan '.$invoice.' dihapus.');
    }

    /**
     * Alur flowchart langkah 4: "Kelola data penjualan historis" — impor CSV.
     *
     * Format kolom: invoice_no | tanggal | pelanggan | kode_produk | qty |
     * harga_satuan | status | pembayaran | catatan. Satu invoice boleh
     * tersebar di beberapa baris (satu baris per item).
     */
    public function import(Request $request)
    {
        $data = $this->validate($request, [
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $content = file_get_contents($data['file']->getRealPath());
        if (str_starts_with($content, "\xEF\xBB\xBF")) {
            $content = substr($content, 3); // buang BOM UTF-8
        }
        if (! mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'Windows-1252');
        }

        $lines = preg_split('/\r\n|\r|\n/', $content);
        $delimiter = $this->detectCsvDelimiter($lines[0] ?? '');

        $header = null;
        $groups = [];
        foreach ($lines as $line) {
            if (trim((string) $line) === '') {
                continue;
            }
            $cols = array_map('trim', str_getcsv($line, $delimiter));
            if ($header === null) {
                $header = array_map(fn ($c) => strtolower(str_replace(' ', '_', $c)), $cols);

                continue;
            }
            $row = array_combine($header, array_pad($cols, count($header), ''));
            $invoice = trim($row['invoice_no'] ?? '');
            if ($invoice === '') {
                continue;
            }
            $groups[$invoice][] = $row;
        }

        if (empty($groups)) {
            return back()->with('error', 'Tidak ada baris data yang terbaca pada file CSV. Periksa kembali format file.');
        }

        $created = 0;
        $itemsCreated = 0;
        $skipped = 0;
        $errors = [];
        $products = Product::get()->keyBy(fn ($p) => mb_strtoupper($p->code));

        DB::transaction(function () use ($groups, $products, &$created, &$itemsCreated, &$skipped, &$errors) {
            foreach ($groups as $invoice => $rows) {
                try {
                    if (Order::where('invoice_no', $invoice)->exists()) {
                        $skipped++;

                        continue;
                    }

                    $customerName = trim($rows[0]['pelanggan'] ?? '') ?: 'Pelanggan Umum';
                    $customer = Customer::firstOrCreate(
                        ['name' => $customerName],
                        ['phone' => null, 'address' => null, 'notes' => null]
                    );

                    $date = $this->parseCsvDate(trim($rows[0]['tanggal'] ?? ''));
                    if (! $date) {
                        $errors[] = $invoice.': tanggal tidak dikenali';

                        continue;
                    }

                    $status = $this->mapCsvLabel(trim($rows[0]['status'] ?? ''), [
                        'menunggu_konfirmasi' => 'menunggu',
                        'diproses' => 'diproses',
                        'siap' => 'siap',
                        'selesai' => 'selesai',
                        'dibatalkan' => 'batal',
                    ]);
                    $payment = $this->mapCsvLabel(trim($rows[0]['pembayaran'] ?? ''), [
                        'lunas' => 'lunas',
                        'dp' => 'dp',
                        'belum_bayar' => 'belum',
                    ]);

                    $order = Order::create([
                        'invoice_no' => $invoice,
                        'customer_id' => $customer->id,
                        'order_date' => $date->format('Y-m-d'),
                        'status' => $status,
                        'payment_status' => $payment,
                        'total' => 0,
                        'notes' => trim($rows[0]['catatan'] ?? '') ?: null,
                    ]);

                    $total = 0.0;
                    foreach ($rows as $row) {
                        $code = mb_strtoupper(trim($row['kode_produk'] ?? ''));
                        $product = $code !== '' ? ($products[$code] ?? null) : null;
                        $qty = $this->parseCsvNumber($row['qty'] ?? '1');
                        $price = $this->parseCsvNumber($row['harga_satuan'] ?? '');
                        if ($price === null) {
                            $price = $product?->price ?? 0.0;
                        }
                        $lineTotal = round($qty * $price, 2);
                        $total += $lineTotal;

                        OrderItem::create([
                            'order_id' => $order->id,
                            'product_id' => $product?->id,
                            'description' => $product?->name ?? ($code !== '' ? $code : 'Item impor'),
                            'unit' => $product?->unit ?? 'pcs',
                            'qty' => $qty,
                            'unit_price' => $price,
                            'total' => $lineTotal,
                        ]);
                        $itemsCreated++;
                    }

                    $order->update(['total' => round($total, 2)]);
                    $created++;
                } catch (\Throwable $e) {
                    $errors[] = $invoice.': '.$e->getMessage();
                }
            }
        });

        $msg = sprintf('Impor selesai: %d pesanan baru (%d baris item), %d dilewati karena invoice sudah ada.', $created, $itemsCreated, $skipped);
        if ($errors) {
            $msg .= ' Perlu perbaikan: '.implode('; ', array_slice($errors, 0, 3));
        }

        return back()->with($errors ? 'error' : 'success', $msg);
    }

    /**
     * Unduh template CSV untuk impor data penjualan (flowchart langkah 4).
     */
    public function importTemplate()
    {
        $header = ['invoice_no', 'tanggal', 'pelanggan', 'kode_produk', 'qty', 'harga_satuan', 'status', 'pembayaran', 'catatan'];
        $rows = [
            $header,
            ['INV-CTH-27102026-001', '27/10/2026', 'Pelanggan Umum', 'SPD-001', '2', '15000', 'Selesai', 'Lunas', 'contoh baris 1'],
            ['INV-CTH-27102026-002', '27/10/2026', 'PT Contoh Sejahtera', 'BKQ-001', '5', '40000', 'Diproses', 'DP', ''],
        ];

        $out = fopen('php://temp', 'r+');
        fwrite($out, "\xEF\xBB\xBF"); // BOM agar Excel membaca UTF-8 dengan benar
        foreach ($rows as $row) {
            fputcsv($out, $row);
        }
        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="template-import-penjualan.csv"',
        ]);
    }

    /** Validasi payload data penjualan (tambah/ubah). */
    private function validateOrder(Request $request, ?Order $order = null): array
    {
        return $this->validate($request, [
            'invoice_no' => ['nullable', 'string', 'max:40', Rule::unique('orders', 'invoice_no')->ignore($order?->id)],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'customer_name' => ['nullable', 'string', 'max:160', 'required_without:customer_id'],
            'customer_phone' => ['nullable', 'string', 'max:40'],
            'order_date' => ['required', 'date'],
            'status' => ['required', Rule::in(array_keys(Order::STATUSES))],
            'payment_status' => ['required', Rule::in(array_keys(Order::PAYMENT_STATUSES))],
            'notes' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', 'integer', 'exists:products,id'],
            'items.*.description' => ['required', 'string', 'max:160'],
            'items.*.qty' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]);
    }

    private function resolveCustomer(array $data): Customer
    {
        if (! empty($data['customer_id'])) {
            return Customer::findOrFail($data['customer_id']);
        }

        return Customer::create([
            'name' => $data['customer_name'],
            'phone' => $data['customer_phone'] ?? null,
            'address' => null,
            'notes' => null,
        ]);
    }

    /** Simpan ulang item pesanan; kembalikan total nilai. */
    private function syncItems(Order $order, array $items): float
    {
        $products = Product::whereIn('id', collect($items)->pluck('product_id')->filter())->get()->keyBy('id');
        $total = 0.0;

        foreach ($items as $it) {
            $product = $it['product_id'] ? ($products[$it['product_id']] ?? null) : null;
            $lineTotal = round((float) $it['qty'] * (float) $it['unit_price'], 2);
            $total += $lineTotal;

            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $it['product_id'] ?? null,
                'description' => $it['description'] ?: ($product?->name ?? 'Item'),
                'unit' => $product?->unit ?? 'pcs',
                'qty' => $it['qty'],
                'unit_price' => $it['unit_price'],
                'total' => $lineTotal,
            ]);
        }

        return $total;
    }

    /** Nomor invoice otomatis: INV-<inisial>-<ddmmyyyy>-<urutan>. */
    private function generateInvoiceNo(Customer $customer, string $date): string
    {
        $initials = $this->initials($customer->name);
        $prefix = 'INV-'.$initials.'-'.Carbon::parse($date)->format('dmY').'-';

        $seq = Order::where('invoice_no', 'like', $prefix.'%')->count() + 1;
        do {
            $candidate = $prefix.str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
            $seq++;
        } while (Order::where('invoice_no', $candidate)->exists());

        return $candidate;
    }

    /** Inisial: huruf pertama dari maksimal 3 kata berhuruf besar. */
    private function initials(string $name): string
    {
        $words = preg_split('/\s+/', trim($name));
        $letters = '';
        foreach ($words as $w) {
            foreach (str_split($w) as $ch) {
                if (ctype_upper($ch)) {
                    $letters .= $ch;

                    break;
                }
            }
            if (mb_strlen($letters) >= 3) {
                break;
            }
        }

        return $letters !== '' ? $letters : 'XXX';
    }

    private function detectCsvDelimiter(string $firstLine): string
    {
        $semicolons = substr_count($firstLine, ';');
        $commas = substr_count($firstLine, ',');

        return $semicolons > $commas ? ';' : ',';
    }

    private function parseCsvDate(string $value): ?Carbon
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        foreach (['d/m/Y', 'd-m-Y', 'd/m/Y H:i', 'd/m/Y H:i:s', 'Y-m-d', 'Y-m-d H:i:s'] as $fmt) {
            try {
                return Carbon::createFromFormat($fmt, $value);
            } catch (\Throwable) {
                // coba format berikutnya
            }
        }
        $ts = strtotime($value);

        return $ts !== false ? Carbon::parse($ts) : null;
    }

    private function parseCsvNumber(string $value): ?float
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        $value = preg_replace('/^(\d+),(\d+)$/', '$1.$2', $value); // "1,5" -> "1.5"

        return is_numeric($value) ? (float) $value : null;
    }

    /** Cocokkan label status/pembayaran dengan kata kunci; default = kunci pertama. */
    private function mapCsvLabel(string $value, array $map): string
    {
        $needle = strtolower($value);
        foreach ($map as $key => $keyword) {
            if ($needle !== '' && str_contains($needle, $keyword)) {
                return $key;
            }
        }

        return array_key_first($map);
    }
}
