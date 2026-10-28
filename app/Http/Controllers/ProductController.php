<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\PredictionRun;
use App\Models\Product;
use App\Models\SalesPrediction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Product::with('category')
            ->withCount(['orderItems as sold_qty' => fn ($q) => $q->select(DB::raw('COALESCE(SUM(qty), 0)'))]);

        if ($q = $request->string('q')->trim()->toString()) {
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                    ->orWhere('code', 'like', "%{$q}%");
            });
        }
        if ($request->filled('category')) {
            $query->where('category_id', $request->integer('category'));
        }
        if ($request->filled('status')) {
            $query->where('is_active', $request->string('status')->toString() === 'active');
        }

        $products = $query->orderBy('sold_qty', 'desc')
            ->paginate(12)
            ->withQueryString();

        $latestRun = PredictionRun::where('status', 'completed')->latest()->first();
        $growth = $latestRun
            ? SalesPrediction::where('prediction_run_id', $latestRun->id)
                ->get()->keyBy('product_id')
            : collect();

        $products->getCollection()->transform(function ($p) use ($growth) {
            $sp = $growth->get($p->id);

            return [
                'id' => $p->id,
                'code' => $p->code,
                'name' => $p->name,
                'category' => $p->category->name,
                'category_id' => $p->category_id,
                'category_color' => $p->category->color,
                'unit' => $p->unit,
                'price' => (float) $p->price,
                'stock' => $p->stock,
                'is_active' => (bool) $p->is_active,
                'sold_qty' => (float) $p->sold_qty,
                'growth_pct' => $sp ? (float) $sp->growth_pct : null,
                'status_label' => $sp?->status_label,
            ];
        });

        return Inertia::render('Product/Index', [
            'products' => $products,
            'filters' => [
                'q' => $request->string('q')->toString(),
                'category' => $request->integer('category', 0),
                'status' => $request->string('status')->toString(),
            ],
            'categories' => Category::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function show(Request $request, Product $product): Response
    {
        $latestRun = PredictionRun::where('status', 'completed')->latest()->first();

        $prediction = $latestRun
            ? SalesPrediction::with('run')
                ->where('prediction_run_id', $latestRun->id)
                ->where('product_id', $product->id)
                ->first()
            : null;

        $history = DB::table('order_items as oi')
            ->join('orders as o', 'o.id', '=', 'oi.order_id')
            ->where('oi.product_id', $product->id)
            ->selectRaw("DATE_FORMAT(o.order_date, '%Y-%m') as bulan, SUM(oi.qty) as qty, SUM(oi.total) as total, COUNT(DISTINCT o.id) as pesanan")
            ->groupBy('bulan')
            ->orderByDesc('bulan')
            ->get()
            ->map(fn ($r) => [
                'bulan' => Carbon::parse($r->bulan.'-01')->translatedFormat('M Y'),
                'qty' => (float) $r->qty,
                'total' => (float) $r->total,
                'pesanan' => (int) $r->pesanan,
            ]);

        $recentItems = $product->orderItems()
            ->with('order.customer')
            ->orderByDesc('id')
            ->limit(10)
            ->get()
            ->map(fn ($i) => [
                'invoice_no' => $i->order->invoice_no,
                'date' => $i->order->order_date->format('d M Y'),
                'customer' => $i->order->customer->name,
                'qty' => (float) $i->qty,
                'unit' => $i->unit,
                'total' => (float) $i->total,
            ]);

        $totalSold = DB::table('order_items')->where('product_id', $product->id)->sum('qty');
        $totalRevenue = DB::table('order_items')->where('product_id', $product->id)->sum('total');

        return Inertia::render('Product/Show', [
            'product' => [
                'id' => $product->id,
                'code' => $product->code,
                'name' => $product->name,
                'category' => $product->category->name,
                'category_id' => $product->category_id,
                'category_color' => $product->category->color,
                'unit' => $product->unit,
                'price' => (float) $product->price,
                'stock' => $product->stock,
                'is_active' => (bool) $product->is_active,
                'description' => $product->name,
            ],
            'summary' => [
                'total_sold' => (float) $totalSold,
                'total_revenue' => (float) $totalRevenue,
                'orders_count' => $product->orderItems()->count('order_id'),
            ],
            'prediction' => $prediction ? [
                'period_start' => $prediction->period_start->format('d M Y'),
                'period_end' => $prediction->period_end->format('d M Y'),
                'predicted_qty' => (float) $prediction->predicted_qty,
                'predicted_revenue' => (float) $prediction->predicted_revenue,
                'actual_qty' => (float) ($prediction->actual_qty ?? 0),
                'growth_pct' => (float) $prediction->growth_pct,
                'status_label' => $prediction->status_label,
                'run_date' => $prediction->run?->finished_at?->format('d M Y H:i'),
                'model_version' => $prediction->run?->model_version,
                'series' => $prediction->series ?? [],
            ] : null,
            'history' => $history,
            'recentItems' => $recentItems,
            'categories' => Category::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validate($request, [
            'name' => ['required', 'string', 'max:160'],
            'category_id' => ['required', 'exists:categories,id'],
            'code' => ['required', 'string', 'max:32', Rule::unique('products', 'code')],
            'unit' => ['required', 'string', 'max:24'],
            'price' => ['required', 'numeric', 'min:0'],
            'stock' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active', true);
        if (! isset($data['stock'])) {
            $data['stock'] = null;
        }

        Product::create($data);

        return back()->with('success', 'Produk '.$data['name'].' ditambahkan.');
    }

    public function update(Request $request, Product $product)
    {
        $data = $this->validate($request, [
            'name' => ['required', 'string', 'max:160'],
            'category_id' => ['required', 'exists:categories,id'],
            'code' => ['required', 'string', 'max:32', Rule::unique('products', 'code')->ignore($product->id)],
            'unit' => ['required', 'string', 'max:24'],
            'price' => ['required', 'numeric', 'min:0'],
            'stock' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active', true);
        if (! isset($data['stock'])) {
            $data['stock'] = null;
        }

        $product->update($data);

        return back()->with('success', 'Perubahan produk '.$product->name.' disimpan.');
    }

    public function destroy(Product $product)
    {
        // Produk dinonaktifkan, bukan dihapus, agar riwayat penjualan tetap utuh.
        $product->update(['is_active' => false]);

        return back()->with('success', 'Produk '.$product->name.' dinonaktifkan.');
    }

    public function activate(Product $product)
    {
        $product->update(['is_active' => true]);

        return back()->with('success', 'Produk '.$product->name.' diaktifkan kembali.');
    }
}
