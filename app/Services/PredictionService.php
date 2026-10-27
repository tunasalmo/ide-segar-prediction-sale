<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\ModelEvaluation;
use App\Models\Order;
use App\Models\PredictionRun;
use App\Models\Product;
use App\Models\SalesPrediction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Mesin prediksi penjualan IDE Segar Printing.
 *
 * Metode (dokumentasi model — lihat juga PRD §8):
 *  1. Agregasi qty per produk per minggu (ISO week) dari order_items.
 *  2. Regresi linier sederhana qty ~ indeks minggu untuk tiap produk
 *     dengan histori >= 4 minggu. Produk dengan data jarang memakai
 *     tren kategori, lalu rata-rata global sebagai fallback.
 *  3. Evaluasi hold-out: K=4 minggu terakhir tidak dipakai untuk fit
 *     model produk; galat dihitung pada minggu-minggu itu (MAE, RMSE,
 *     MAPE). MAPE hanya dihitung pada titik dengan aktual > 0.
 *  4. Horizon prediksi: H hari setelah data terakhir. Nilai harian
 *     disimpan sebagai deret JSON per produk agar grafik 7/30/90 hari
 *     ditarik dari basis data, bukan dihitung ulang di sisi klien.
 *  5. Pertumbuhan dihitung terhadap periode aktual dengan panjang sama
 *     yang tepat sebelum horizon. Jika penyebut nol/tidak ada, growth
 *     dikosongkan (tidak membagi nol).
 *  6. Label prioritas (aturan terdokumentasi):
 *       growth >= 20% dan prediksi >= 10 qty  -> prioritas_stok
 *       growth >= 5%                          -> pantau
 *       selainnya                              -> normal
 */
class PredictionService
{
    public const MODEL_VERSION = 'linreg-weekly-v1';

    /** Jumlah minggu terakhir yang disisihkan sebagai data uji. */
    public const HOLDOUT_WEEKS = 4;

    /** Jumlah minggu minimal agar sebuah produk di-fit sendiri. */
    public const MIN_WEEKS = 4;

    public function run(int $horizonDays = 90): PredictionRun
    {
        $run = PredictionRun::create([
            'status' => 'running',
            'horizon_days' => $horizonDays,
            'model_version' => self::MODEL_VERSION,
            'started_at' => now(),
        ]);

        try {
            [$weeks] = $this->weeklyGrid();
            $dataStart = Carbon::parse(Order::min('order_date'));
            $dataEnd = Carbon::parse(Order::max('order_date'));

            // qty per produk per minggu
            $matrix = $this->productWeeklyMatrix($weeks);
            $categoryMatrix = $this->categoryWeeklyMatrix($weeks);

            // Model regresi level toko (flowchart: hitung koefisien a dan b,
            // bentuk model Y = a + bX dari total qty per minggu).
            $shop = $this->shopLevelModel($matrix, $weeks);

            // qty aktual per produk pada jendela pembanding [dataEnd-H+1, dataEnd]
            $prevWindowStart = $dataEnd->copy()->subDays($horizonDays - 1);
            $comparison = $this->actualQtyInRange($prevWindowStart->format('Y-m-d'), $dataEnd->format('Y-m-d'));

            $products = Product::with('category')->where('is_active', true)->get();

            // --- Evaluasi hold-out (agregat semua produk) ---
            $evalErrors = $this->holdoutErrors($matrix);

            $mae = $evalErrors['mae'];
            $rmse = $evalErrors['rmse'];
            $mape = $evalErrors['mape']; // null bila tak ada titik aktual > 0
            $coverage = $evalErrors['coverage'];

            foreach ([
                ['MAE', $mae, ['periode' => 'hold-out '.self::HOLDOUT_WEEKS.' minggu terakhir', 'satuan' => 'qty per minggu']],
                ['RMSE', $rmse, ['periode' => 'hold-out '.self::HOLDOUT_WEEKS.' minggu terakhir', 'satuan' => 'qty per minggu']],
                ['MAPE', $mape, ['periode' => 'hold-out '.self::HOLDOUT_WEEKS.' minggu terakhir', 'level' => 'total mingguan toko', 'catatan' => 'MAPE per produk tidak dipakai karena permintaan intermittent (PRD §8)', 'persen' => true]],
                ['coverage', $coverage, ['keterangan' => 'proporsi produk dengan data cukup untuk di-fit sendiri']],
            ] as [$metric, $value, $detail]) {
                if ($value !== null) {
                    ModelEvaluation::create([
                        'prediction_run_id' => $run->id,
                        'metric' => $metric,
                        'value' => round($value, 4),
                        'detail' => $detail,
                    ]);
                }
            }

            // --- Prediksi per produk ---
            $horizonStart = $dataEnd->copy()->addDay();
            $horizonEnd = $dataEnd->copy()->addDays($horizonDays);
            $categoryTrends = $this->categoryTrends($categoryMatrix);
            $globalDaily = $this->globalDailyRate($matrix, count($weeks));

            $growing = 0;
            $priorityCount = 0;

            foreach ($products as $product) {
                $series = $this->seriesFor($product, $matrix, $categoryMatrix, $categoryTrends, $globalDaily, $weeks, $horizonStart, $horizonDays);
                $predictedQty = round(array_sum(array_values($series)), 2);

                $prevQty = $comparison[$product->id] ?? 0.0;
                $growth = $prevQty > 0
                    ? round(($predictedQty - $prevQty) / $prevQty * 100, 2)
                    : null;

                $label = 'normal';
                if ($growth !== null && $growth >= 20 && $predictedQty >= 10) {
                    $label = 'prioritas_stok';
                } elseif ($growth !== null && $growth >= 5) {
                    $label = 'pantau';
                }

                if ($growth !== null && $growth > 0) {
                    $growing++;
                }
                if ($label === 'prioritas_stok') {
                    $priorityCount++;
                }

                SalesPrediction::create([
                    'prediction_run_id' => $run->id,
                    'product_id' => $product->id,
                    'category_id' => $product->category_id,
                    'period_start' => $horizonStart->format('Y-m-d'),
                    'period_end' => $horizonEnd->format('Y-m-d'),
                    'predicted_qty' => $predictedQty,
                    'predicted_revenue' => round($predictedQty * $product->price, 2),
                    'actual_qty' => $prevQty,
                    'growth_pct' => $growth,
                    'score' => round(($predictedQty * max($growth ?? 0, 0) / 100) + $predictedQty, 4),
                    'status_label' => $label,
                    'series' => $series,
                ]);
            }

            $run->update([
                'status' => 'completed',
                'data_start' => $dataStart->format('Y-m-d'),
                'data_end' => $dataEnd->format('Y-m-d'),
                'model_a' => round($shop['a'], 6),
                'model_b' => round($shop['b'], 6),
                'periods_count' => $shop['n'],
                'predicted_revenue_total' => SalesPrediction::where('prediction_run_id', $run->id)->sum('predicted_revenue'),
                'predicted_orders_total' => $this->orderForecastSum($weeks, $horizonDays),
                'accuracy_pct' => $mape !== null ? round(max(100 - $mape, 0), 1) : null,
                'finished_at' => now(),
            ]);

            $this->notify($run, $products, $growing, $priorityCount, $mape);

            return $run->fresh();
        } catch (\Throwable $e) {
            $run->update([
                'status' => 'failed',
                'error' => $e->getMessage().' @ '.$e->getFile().':'.$e->getLine(),
                'finished_at' => now(),
            ]);

            throw $e;
        }
    }

    /**
     * Grid minggu ISO yang mencakup seluruh data penjualan.
     *
     * @return array{0: string[], 1: string[]} [kunci "Y-W", tanggal Senin tiap minggu]
     */
    private function weeklyGrid(): array
    {
        $first = Order::min('order_date');
        $last = Order::max('order_date');

        $start = Carbon::parse($first)->startOfWeek();
        $end = Carbon::parse($last);

        $weeks = [];
        $dates = [];
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $weeks[] = $cursor->isoWeekYear.'-'.$cursor->isoWeek();
            $dates[] = $cursor->format('Y-m-d');
            $cursor->addWeek();
        }

        return [$weeks, $dates];
    }

    /**
     * Matriks qty per produk per minggu.
     *
     * @return array<int, float[]> product_id => [qty per minggu]
     */
    private function productWeeklyMatrix(array $weeks): array
    {
        $rows = DB::table('order_items as oi')
            ->join('orders as o', 'o.id', '=', 'oi.order_id')
            ->whereNotNull('oi.product_id')
            ->selectRaw('oi.product_id, YEARWEEK(o.order_date, 3) as yw, SUM(oi.qty) as qty')
            ->groupBy('oi.product_id', 'yw')
            ->get();

        $matrix = [];
        foreach ($rows as $r) {
            $year = intdiv((int) $r->yw, 100);
            $week = (int) $r->yw % 100;
            $matrix[(int) $r->product_id][$year.'-'.$week] = (float) $r->qty;
        }

        return $this->padMatrix($matrix, $weeks);
    }

    /**
     * Matriks qty per kategori per minggu.
     *
     * @return array<int, float[]> category_id => [qty per minggu]
     */
    private function categoryWeeklyMatrix(array $weeks): array
    {
        $rows = DB::table('order_items as oi')
            ->join('orders as o', 'o.id', '=', 'oi.order_id')
            ->join('products as p', 'p.id', '=', 'oi.product_id')
            ->selectRaw('p.category_id, YEARWEEK(o.order_date, 3) as yw, SUM(oi.qty) as qty')
            ->groupBy('p.category_id', 'yw')
            ->get();

        $matrix = [];
        foreach ($rows as $r) {
            $year = intdiv((int) $r->yw, 100);
            $week = (int) $r->yw % 100;
            $matrix[(int) $r->category_id][$year.'-'.$week] = (float) $r->qty;
        }

        return $this->padMatrix($matrix, $weeks);
    }

    private function padMatrix(array $matrix, array $weeks): array
    {
        foreach ($matrix as $id => &$series) {
            $padded = [];
            foreach ($weeks as $w) {
                $padded[$w] = $series[$w] ?? 0.0;
            }
            $series = $padded;
        }

        return $matrix;
    }

    /**
     * Model regresi level toko: Y = a + b·X dengan Y = total qty per minggu
     * (semua produk) dan X = indeks minggu 1..n. Koefisien inilah yang
     * ditampilkan di antarmuka sebagai model sistem usulan.
     *
     * @return array{a: float, b: float, n: int, total: float}
     */
    private function shopLevelModel(array $matrix, array $weeks): array
    {
        $totals = array_fill(0, count($weeks), 0.0);
        foreach ($matrix as $series) {
            foreach (array_values($series) as $i => $v) {
                $totals[$i] += $v;
            }
        }
        [$a, $b] = $this->linreg($totals);

        return [
            'a' => $a,
            'b' => $b,
            'n' => count($weeks),
            'total' => array_sum($totals),
        ];
    }

    /**
     * Regresi linier sederhana y = a + b·x pada indeks 1..n.
     *
     * @return array{0: float, 1: float} [intercept a, slope b]
     */
    private function linreg(array $y): array
    {
        $n = count($y);
        if ($n < 2) {
            return [$y[0] ?? 0.0, 0.0];
        }

        $sx = array_sum(range(1, $n));
        $sy = array_sum($y);
        $sxx = 0.0;
        $sxy = 0.0;
        foreach ($y as $i => $v) {
            $x = $i + 1;
            $sxx += $x * $x;
            $sxy += $x * $v;
        }
        $denom = $n * $sxx - $sx * $sx;
        if (abs($denom) < 1e-9) {
            return [array_sum($y) / $n, 0.0];
        }
        $b = ($n * $sxy - $sx * $sy) / $denom;
        $a = ($sy - $b * $sx) / $n;

        return [$a, $b];
    }

    /**
     * Tren slope per kategori (dipakai untuk produk dengan data jarang).
     *
     * @return array<int, array{0: float, 1: float}>
     */
    private function categoryTrends(array $categoryMatrix): array
    {
        $trends = [];
        foreach ($categoryMatrix as $catId => $series) {
            $nonzero = array_values(array_filter($series, fn ($v) => $v > 0));
            if (count($nonzero) >= self::MIN_WEEKS) {
                $trends[$catId] = $this->linreg(array_values($series));
            }
        }

        return $trends;
    }

    /**
     * Rata-rata qty harian global (fallback terakhir).
     */
    private function globalDailyRate(array $matrix, int $weekCount): float
    {
        $total = 0.0;
        foreach ($matrix as $series) {
            $total += array_sum($series);
        }
        if ($weekCount <= 0) {
            return 0.0;
        }

        return $total / ($weekCount * 7);
    }

    /**
     * Deret prediksi harian untuk satu produk.
     *
     * Aturan fallback (terdokumentasi):
     *  - Produk dengan >= 4 minggu penjualan: model sendiri (regresi linier),
     *    dengan proyeksi mingguan dibatasi maksimal 4x maksimum mingguan
     *    historis produk agar ekstrapolasi tidak melonjak tak terkendali.
     *  - Produk jarang: tren kategori dibagi sesuai pangsa produk terhadap
     *    kategori (atau rata-rata produk dalam kategori bila tanpa riwayat).
     *  - Terakhir: rata-rata harian global dibagi pangsa produk.
     *
     * @return array<string, float> date => qty
     */
    private function seriesFor(
        Product $product,
        array $matrix,
        array $categoryMatrix,
        array $categoryTrends,
        float $globalDaily,
        array $weeks,
        Carbon $horizonStart,
        int $horizonDays
    ): array {
        $productSeries = $matrix[$product->id] ?? array_fill(0, count($weeks), 0.0);
        $n = count($weeks);

        $nonzeroCount = count(array_filter($productSeries, fn ($v) => $v > 0));
        $productTotal = array_sum($productSeries);
        $categorySeries = $categoryMatrix[$product->category_id] ?? [];
        $categoryTotal = array_sum($categorySeries);

        $capPerWeek = max(array_values($productSeries)) * 4;
        $share = 0.0;
        $model = null;

        if ($nonzeroCount >= self::MIN_WEEKS) {
            [$a, $b] = $this->linreg(array_values($productSeries));
            $model = [$a, $b, 'produk'];
        } elseif (isset($categoryTrends[$product->category_id]) && $categoryTotal > 0) {
            [$a, $b] = $categoryTrends[$product->category_id];
            $share = $productTotal > 0
                ? $productTotal / $categoryTotal
                : (1 / max(Product::where('category_id', $product->category_id)->count(), 1));
            $model = [$a, $b, 'kategori'];
        } else {
            $model = [0.0, 0.0, 'global'];
            $share = $productTotal > 0 && $globalDaily > 0
                ? $productTotal / array_sum(array_map('array_sum', $matrix))
                : 1.0;
        }

        $series = [];
        $cursor = $horizonStart->copy();
        for ($d = 0; $d < $horizonDays; $d++) {
            $weekIndex = $n + intdiv($d, 7); // minggu ke-n+1 dst
            [$a, $b, $mode] = $model;

            $weekly = match ($mode) {
                'kategori' => max($a + $b * $weekIndex, 0.0) * $share,
                'global' => $globalDaily * $share * 7,
                default => max($a + $b * $weekIndex, 0.0),
            };

            // pembatas ekstrapolasi: 4x maksimum mingguan historis
            if ($mode === 'produk' && $capPerWeek > 0) {
                $weekly = min($weekly, $capPerWeek);
            }

            $series[$cursor->format('Y-m-d')] = round($weekly / 7.0, 4);
            $cursor->addDay();
        }

        return $series;
    }

    /**
     * Jumlah pesanan (order) per minggu pada grid yang sama.
     *
     * @return float[] [jumlah pesanan per minggu]
     */
    private function weeklyOrderCounts(array $weeks): array
    {
        $rows = DB::table('orders')
            ->selectRaw('YEARWEEK(order_date, 3) as yw, COUNT(*) as cnt')
            ->groupBy('yw')
            ->get();

        $map = [];
        foreach ($rows as $r) {
            $year = intdiv((int) $r->yw, 100);
            $week = (int) $r->yw % 100;
            $map[$year.'-'.$week] = (int) $r->cnt;
        }

        $padded = [];
        foreach ($weeks as $w) {
            $padded[] = (float) ($map[$w] ?? 0);
        }

        return $padded;
    }

    /**
     * Perkiraan jumlah pesanan pada horizon, dari regresi linier
     * jumlah pesanan mingguan (level toko).
     */
    private function orderForecastSum(array $weeks, int $horizonDays): float
    {
        $series = $this->weeklyOrderCounts($weeks);
        [$a, $b] = $this->linreg($series);
        $n = count($weeks);
        $weeksAhead = (int) ceil($horizonDays / 7);
        $sum = 0.0;
        for ($w = 1; $w <= $weeksAhead; $w++) {
            $weekly = max($a + $b * ($n + $w), 0.0);
            $sum += $weekly * (min($horizonDays - ($w - 1) * 7, 7) / 7);
        }

        return round($sum, 1);
    }

    /**
     * Qty aktual per produk pada rentang tanggal.
     *
     * @return array<int, float> product_id => qty
     */
    private function actualQtyInRange(string $from, string $to): array
    {
        $rows = DB::table('order_items as oi')
            ->join('orders as o', 'o.id', '=', 'oi.order_id')
            ->whereBetween('o.order_date', [$from, $to])
            ->whereNotNull('oi.product_id')
            ->selectRaw('oi.product_id, SUM(oi.qty) as qty')
            ->groupBy('oi.product_id')
            ->get();

        $map = [];
        foreach ($rows as $r) {
            $map[(int) $r->product_id] = (float) $r->qty;
        }

        return $map;
    }

    /**
     * Evaluasi hold-out: model di-fit tanpa K minggu terakhir, lalu galat
     * dihitung pada K minggu itu.
     *
     * Pemilihan metrik (PRD §8: "pilih metrik yang sesuai dengan data"):
     *  - MAE/RMSE dihitung per produk per minggu — mengukur galat satuan qty.
     *  - MAPE dihitung pada total mingguan level toko, bukan per produk,
     *    karena permintaan produk individual bersifat intermittent (lonjakan
     *    pesanan besar di minggu tertentu) sehingga MAPE per produk didominasi
     *    titik aktual kecil dan kehilangan makna. Total mingguan toko jauh
     *    lebih stabil dan mencerminkan galat yang relevan bagi pengelola.
     *
     * @return array{mae: float, rmse: float, mape: ?float, coverage: float}
     */
    private function holdoutErrors(array $matrix): array
    {
        $absErrors = [];
        $sqErrors = [];
        $pctErrors = [];
        $shopPredicted = [];
        $shopActual = [];
        $fitted = 0;
        $total = 0;

        foreach ($matrix as $series) {
            $total++;
            $values = array_values($series);
            $n = count($values);
            if ($n < self::MIN_WEEKS + self::HOLDOUT_WEEKS) {
                continue;
            }

            $train = array_slice($values, 0, $n - self::HOLDOUT_WEEKS);
            $holdout = array_slice($values, $n - self::HOLDOUT_WEEKS);
            [$a, $b] = $this->linreg($train);
            $fitted++;

            foreach ($holdout as $i => $actual) {
                $pred = max($a + $b * (count($train) + $i + 1), 0.0);
                $err = $pred - $actual;
                $absErrors[] = abs($err);
                $sqErrors[] = $err * $err;
                $shopPredicted[$i] = ($shopPredicted[$i] ?? 0.0) + $pred;
                $shopActual[$i] = ($shopActual[$i] ?? 0.0) + $actual;
            }
        }

        // MAPE pada level toko: total mingguan aktual vs prediksi
        foreach ($shopActual as $i => $actual) {
            if ($actual > 0) {
                $pctErrors[] = abs($shopPredicted[$i] - $actual) / $actual * 100;
            }
        }

        $count = count($absErrors);

        return [
            'mae' => $count ? array_sum($absErrors) / $count : 0.0,
            'rmse' => $count ? sqrt(array_sum($sqErrors) / $count) : 0.0,
            'mape' => count($pctErrors) ? array_sum($pctErrors) / count($pctErrors) : null,
            'coverage' => $total ? round($fitted / $total, 4) : 0.0,
        ];
    }

    private function notify(PredictionRun $run, $products, int $growing, int $priorityCount, ?float $mape): void
    {
        $users = User::all();
        if ($users->isEmpty()) {
            return;
        }

        $accuracy = $mape !== null ? round(max(100 - $mape, 0), 1) : null;

        foreach ($users as $user) {
            $msg = 'Prediksi '.$run->horizon_days.' hari berhasil dibuat ('.$run->data_start->format('d M').'–'.$run->data_end->format('d M').' 2026). '
                .$growing.' produk diprediksi tumbuh, '.$priorityCount.' perlu prioritas stok.';
            if ($accuracy !== null) {
                $msg .= ' Akurasi evaluasi: '.number_format($accuracy, 1).'% (100 − MAPE, hold-out).';
            }
            AppNotification::create([
                'user_id' => $user->id,
                'type' => 'prediction',
                'title' => 'Prediksi terbaru selesai',
                'body' => $msg,
                'link' => '/prediksi',
            ]);
        }

        // Lonjakan permintaan untuk produk prioritas stok
        $top = SalesPrediction::where('prediction_run_id', $run->id)
            ->where('status_label', 'prioritas_stok')
            ->with('product')
            ->orderByDesc('growth_pct')
            ->limit(3)
            ->get();

        foreach ($top as $sp) {
            foreach ($users as $user) {
                AppNotification::create([
                    'user_id' => $user->id,
                    'type' => 'spike',
                    'title' => 'Lonjakan permintaan: '.$sp->product->name,
                    'body' => 'Diprediksi naik '.number_format($sp->growth_pct, 1).'% dibanding periode sebelumnya. Periksa stok bahan dan jadwal produksi.',
                    'link' => '/produk/'.$sp->product_id,
                ]);
            }
        }
    }
}
