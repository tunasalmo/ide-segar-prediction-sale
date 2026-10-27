<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request): Response
    {
        [$from, $to, $categoryId, $groupBy] = $this->filters($request);
        $rows = $this->rows($from, $to, $categoryId, $groupBy);

        $summary = [
            'revenue' => (float) $rows->sum('total'),
            'qty' => (float) $rows->sum('qty'),
            'orders' => (int) Order::whereBetween('order_date', [$from, $to])
                ->when($categoryId, fn ($q) => $q->whereHas('items.product', fn ($p) => $p->where('category_id', $categoryId)))
                ->count(),
        ];

        // tren pendapatan harian pada rentang
        $daily = DB::table('orders')
            ->selectRaw('order_date, SUM(total) as total')
            ->whereBetween('order_date', [$from, $to])
            ->groupBy('order_date')
            ->orderBy('order_date')
            ->get();

        $labels = [];
        $values = [];
        $cursor = Carbon::parse($from)->copy();
        while ($cursor->lte(Carbon::parse($to))) {
            $labels[] = $cursor->format('d M');
            $values[] = (float) (collect($daily)->firstWhere('order_date', $cursor->format('Y-m-d'))?->total ?? 0);
            $cursor->addDay();
        }

        return Inertia::render('Report/Index', [
            'filters' => [
                'from' => $from,
                'to' => $to,
                'category' => $categoryId,
                'group_by' => $groupBy,
            ],
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'summary' => $summary,
            'rows' => $rows,
            'daily' => ['labels' => $labels, 'values' => $values],
            'generated_at' => now()->format('d M Y H:i'),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        [$from, $to, $categoryId, $groupBy] = $this->filters($request);
        $rows = $this->rows($from, $to, $categoryId, $groupBy);

        $filename = 'laporan-penjualan-'.$from.'-'.$to.'.csv';

        return response()->streamDownload(function () use ($rows, $groupBy) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Nama', 'Kategori', 'Qty', 'Jumlah Pesanan', 'Pendapatan (Rp)']);
            foreach ($rows as $r) {
                fputcsv($out, [$r->name, $r->category, number_format((float) $r->qty, 2), (int) $r->orders, (float) $r->total]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function filters(Request $request): array
    {
        $from = $request->string('from')->toString() ?: Carbon::parse(Order::min('order_date'))->format('Y-m-d');
        $to = $request->string('to')->toString() ?: Carbon::parse(Order::max('order_date'))->format('Y-m-d');
        $categoryId = $request->integer('category', 0);
        $groupBy = $request->string('group_by')->toString() ?: 'product';

        return [$from, $to, $categoryId, $groupBy];
    }

    private function rows(string $from, string $to, int $categoryId, string $groupBy)
    {
        $query = DB::table('order_items as oi')
            ->join('orders as o', 'o.id', '=', 'oi.order_id')
            ->join('products as p', 'p.id', '=', 'oi.product_id')
            ->join('categories as c', 'c.id', '=', 'p.category_id')
            ->whereBetween('o.order_date', [$from, $to])
            ->when($categoryId, fn ($q) => $q->where('p.category_id', $categoryId));

        if ($groupBy === 'category') {
            return $query
                ->selectRaw('c.name as name, c.name as category, SUM(oi.qty) as qty, COUNT(DISTINCT o.id) as orders, SUM(oi.total) as total')
                ->groupBy('c.id', 'c.name')
                ->orderByDesc('total')
                ->get();
        }

        return $query
            ->selectRaw('p.name as name, c.name as category, SUM(oi.qty) as qty, COUNT(DISTINCT o.id) as orders, SUM(oi.total) as total')
            ->groupBy('p.id', 'p.name', 'c.name')
            ->orderByDesc('total')
            ->get();
    }
}
