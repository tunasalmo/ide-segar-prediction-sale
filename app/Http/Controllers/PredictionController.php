<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\ModelEvaluation;
use App\Models\PredictionRun;
use App\Models\SalesPrediction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PredictionController extends Controller
{
    /**
     * Halaman Prediksi Penjualan.
     */
    public function index(Request $request): Response
    {
        $run = PredictionRun::where('status', 'completed')->latest()->first();

        if (! $run) {
            return Inertia::render('Prediction/Index', [
                'run' => null,
                'hint' => 'Belum ada hasil prediksi. Jalankan `php artisan predictions:run` untuk membuat prediksi pertama.',
            ]);
        }

        $eval = ModelEvaluation::where('prediction_run_id', $run->id)
            ->get()
            ->keyBy('metric');

        $growing = SalesPrediction::where('prediction_run_id', $run->id)
            ->where('growth_pct', '>', 0)->count();
        $priority = SalesPrediction::where('prediction_run_id', $run->id)
            ->where('status_label', 'prioritas_stok')->count();

        // ---- Insight mingguan (aturan deterministik) ----
        $candidates = SalesPrediction::with('product')
            ->where('prediction_run_id', $run->id)
            ->where('growth_pct', '>', 0)
            ->where('predicted_qty', '>=', 5)
            ->orderByDesc('growth_pct')
            ->limit(6)
            ->get();

        $insight = null;
        if ($candidates->isNotEmpty()) {
            $best = $candidates->first();
            $insight = [
                'title' => 'Permintaan '.$best->product->name.' diprediksi meningkat',
                'body' => 'Perkiraan naik '.number_format((float) $best->growth_pct, 1).'% dibanding '
                    .$run->data_end->copy()->subDays($run->horizon_days - 1)->format('d M').'–'
                    .$run->data_end->format('d M').'. '
                    .'Estimasi '.number_format((float) $best->predicted_qty, 0, ',', '.').' '.$best->product->unit
                    .' untuk periode '.$run->data_end->copy()->addDay()->format('d M').'–'
                    .$run->data_end->copy()->addDays($run->horizon_days)->format('d M').'. '
                    .'Tren ini didorong pola historis kategori '.$best->product->category->name.'.',
                'product_id' => $best->product_id,
                'product_code' => $best->product->code,
                'product_name' => $best->product->name,
                'growth_pct' => (float) $best->growth_pct,
            ];
        }

        // ---- Data grafik: aktual harian (pendapatan) + prediksi harian (dari deret tersimpan) ----
        $rangeOptions = ['7' => '7 Hari', '30' => '30 Hari', '90' => '3 Bulan'];
        $actualDaily = DB::table('orders')
            ->selectRaw('order_date, SUM(total) as total')
            ->whereBetween('order_date', [$run->data_start, $run->data_end])
            ->groupBy('order_date')
            ->orderBy('order_date')
            ->get()
            ->keyBy('order_date');

        // prediksi harian: qty per produk x harga, dijumlahkan semua produk
        $predictedDaily = [];
        foreach (SalesPrediction::where('prediction_run_id', $run->id)->get() as $sp) {
            $price = $sp->product->price;
            foreach (($sp->series ?? []) as $date => $qty) {
                $predictedDaily[$date] = ($predictedDaily[$date] ?? 0.0) + $qty * $price;
            }
        }
        ksort($predictedDaily);

        $build = function (int $days) use ($run, $actualDaily, $predictedDaily) {
            $startActual = $run->data_end->copy()->subDays($days - 1);
            $labels = [];
            $actual = [];
            $predicted = [];
            $cursor = $startActual->copy();
            while ($cursor->lte($run->data_end)) {
                $labels[] = $cursor->format('d M');
                $actual[] = (float) ($actualDaily[$cursor->format('Y-m-d')]->total ?? 0);
                $predicted[] = null;
                $cursor->addDay();
            }
            $cursor = $run->data_end->copy()->addDay();
            for ($d = 0; $d < $days; $d++) {
                $labels[] = $cursor->format('d M');
                $actual[] = null;
                $predicted[] = round($predictedDaily[$cursor->format('Y-m-d')] ?? 0, 0);
                $cursor->addDay();
            }

            return ['labels' => $labels, 'actual' => $actual, 'predicted' => $predicted];
        };

        // ---- Distribusi kategori (kontribusi pendapatan aktual) ----
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
        $shareTotal = $categoryShare->sum('total');

        // ---- Produk dengan potensi tertinggi ----
        $topProducts = SalesPrediction::with('product')
            ->where('prediction_run_id', $run->id)
            ->orderByDesc('score')
            ->get()
            ->map(fn ($sp) => [
                'id' => $sp->product_id,
                'code' => $sp->product->code,
                'name' => $sp->product->name,
                'category' => $sp->product->category->name,
                'unit' => $sp->product->unit,
                'actual_qty' => (float) ($sp->actual_qty ?? 0),
                'predicted_qty' => (float) $sp->predicted_qty,
                'growth_pct' => $sp->growth_pct !== null ? (float) $sp->growth_pct : null,
                'status_label' => $sp->status_label,
            ]);

        return Inertia::render('Prediction/Index', [
            'run' => [
                'id' => $run->id,
                'horizon_days' => $run->horizon_days,
                'data_start' => $run->data_start?->format('d M Y'),
                'data_end' => $run->data_end?->format('d M Y'),
                'period_start' => $run->data_end->copy()->addDay()->format('d M Y'),
                'period_end' => $run->data_end->copy()->addDays($run->horizon_days)->format('d M Y'),
                'predicted_revenue_total' => (float) $run->predicted_revenue_total,
                'predicted_orders_total' => (float) $run->predicted_orders_total,
                'accuracy_pct' => (float) $run->accuracy_pct,
                'model_version' => $run->model_version,
                'updated_at' => $run->finished_at?->format('d M Y H:i'),
            ],
            'evaluation' => [
                'mae' => (float) ($eval['MAE']->value ?? 0),
                'rmse' => (float) ($eval['RMSE']->value ?? 0),
                'mape' => (float) ($eval['MAPE']->value ?? 0),
                'coverage' => (float) ($eval['coverage']->value ?? 0),
                'map_detail' => $eval['MAPE']?->detail ?? [],
            ],
            'cards' => [
                'growing' => $growing,
                'priority' => $priority,
            ],
            'insight' => $insight,
            'rangeOptions' => $rangeOptions,
            'charts' => [
                'projection7' => $build(7),
                'projection30' => $build(30),
                'projection90' => $build(90),
                'actualDataEnd' => $run->data_end->format('Y-m-d'),
            ],
            'categoryShare' => $categoryShare,
            'shareTotal' => $shareTotal,
            'topProducts' => $topProducts,
            'allTop' => SalesPrediction::with('product')
                ->where('prediction_run_id', $run->id)
                ->where('growth_pct', '>', 0)
                ->orderByDesc('score')
                ->count(),
        ]);
    }
}
