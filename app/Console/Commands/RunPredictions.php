<?php

namespace App\Console\Commands;

use App\Services\PredictionService;
use Illuminate\Console\Command;

class RunPredictions extends Command
{
    protected $signature = 'predictions:run {--horizon=90 : Jumlah hari horizon prediksi}';

    protected $description = 'Menjalankan mesin prediksi penjualan dan menyimpan hasilnya';

    public function handle(PredictionService $service): int
    {
        $horizon = (int) $this->option('horizon');
        $this->info("Menjalankan prediksi, horizon {$horizon} hari...");

        try {
            $run = $service->run($horizon);
            $this->info("Selesai: run #{$run->id} ({$run->status}).");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Gagal: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
