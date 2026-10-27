<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\PredictionRun;
use App\Models\SalesPrediction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $today = Carbon::today();
        $from30 = $today->copy()->subDays(29);
        $from60 = $today->copy()->subDays(59);

        $revenueAll = Order::sum('total');
        $revenue30 = Order::whereBetween('order_date', [$from30, $today])->sum('total');
        $revenuePrev30 = Order::whereBetween('order_date', [$from60, $from30->copy()->subDay()])->sum('total');
        $orders30 = Order::whereBetween('order_date', [$from30, $today])->count();
        $ordersPrev30 = Order::whereBetween('order_date', [$from60, $from30->copy()->subDay()])->count();
        $unpaid = Order::where('payment_status', '!=', 'lunas')->sum('total');
        $attention = Order::where('status', 'menunggu_konfirmasi')->count();

        // tren pendapatan harian 30 hari
        $trend = DB::table('orders')
            ->selectRaw('order_date, SUM(total) as total')
            ->whereBetween('order_date', [$from30, $today])
            ->groupBy('order_date')
            ->orderBy('order_date')
            ->get()
            ->keyBy('order_date');

        $labels = [];
        $values = [];
        $cursor = $from30->copy();
        while ($cursor->lte($today)) {
            $labels[] = $cursor->format('d M');
            $values[] = (float) ($trend[$cursor->format('Y-m-d')]->total ?? 0);
            $cursor->addDay();
        }

        // kontribusi kategori (pendapatan aktual, seluruh periode)
        $categoryShare = DB::table('order_items as oi')
            ->join('orders as o', 'o.id', '=', 'oi.order_id')
            ->join('products as p', 'p.id', '=', 'oi.product_id')
            ->join('categories as c', 'c.id', '=', 'p.category_id')
            ->selectRaw('c.name, c.color, SUM(oi.total) as total')
            ->groupBy('c.id', 'c.name', 'c.color')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($r) => [
                'name' => $r->name,
                'color' => $r->color,
                'total' => (float) $r->total,
            ]);

        $recentOrders = Order::with('customer')
            ->orderByDesc('order_date')
            ->limit(6)
            ->get()
            ->map(fn ($o) => [
                'id' => $o->id,
                'invoice_no' => $o->invoice_no,
                'customer' => $o->customer->name,
                'order_date' => $o->order_date->format('d M Y'),
                'total' => (float) $o->total,
                'status' => $o->status,
                'payment_status' => $o->payment_status,
            ]);

        $run = PredictionRun::where('status', 'completed')->latest()->first();
        $recommendations = $run
            ? SalesPrediction::with('product')
                ->where('prediction_run_id', $run->id)
                ->where('growth_pct', '>', 0)
                ->orderByDesc('score')
                ->limit(5)
                ->get()
                ->map(fn ($sp) => [
                    'id' => $sp->product->id,
                    'code' => $sp->product->code,
                    'name' => $sp->product->name,
                    'growth_pct' => (float) $sp->growth_pct,
                    'status_label' => $sp->status_label,
                    'predicted_qty' => (float) $sp->predicted_qty,
                    'unit' => $sp->product->unit,
                ])
            : collect();

        return Inertia::render('Dashboard', [
            'metrics' => [
                'revenue_all' => $revenueAll,
                'revenue_30d' => $revenue30,
                'revenue_delta' => $revenuePrev30 > 0 ? round(($revenue30 - $revenuePrev30) / $revenuePrev30 * 100, 1) : null,
                'orders_30d' => $orders30,
                'orders_delta' => $ordersPrev30 > 0 ? round(($orders30 - $ordersPrev30) / $ordersPrev30 * 100, 1) : null,
                'customers' => Customer::count(),
                'products' => Product::where('is_active', true)->count(),
                'unpaid' => $unpaid,
                'attention' => $attention,
            ],
            'trend' => ['labels' => $labels, 'values' => $values],
            'categoryShare' => $categoryShare,
            'recentOrders' => $recentOrders,
            'recommendations' => $recommendations,
            'prediction' => $run ? [
                'accuracy_pct' => (float) $run->accuracy_pct,
                'predicted_revenue_total' => (float) $run->predicted_revenue_total,
                'updated_at' => $run->finished_at?->format('d M Y H:i'),
            ] : null,
        ]);
    }
}
