# IDE Segar Printing — Sales Prediction Dashboard

Dashboard admin internal untuk usaha percetakan **IDE Segar Printing** (CV. Faruqi
Mandiri Sejahtera): ringkasan penjualan, prediksi permintaan, dan rekomendasi
prioritas stok. Dibangun sesuai [PRD](docs/PRD_IDE_Segar_Printing.md).

Stack: Laravel 12 · Vue 3 + Inertia.js 2 · Tailwind CSS · Chart.js · MySQL 8.

## Data sumber

`Detail penjualan ide segar.xlsx` — detail penjualan 1 Mei – 17 Okt 2026
(214 invoice, 301 baris item, omzet Rp 39.135.000, sesuai baris TOTAL pada
sheet). Diimpor via seeder ke tabel `products`, `customers`, `orders`,
`order_items`, `categories` (script regenerasi seed:
`scripts/build-seed-from-xlsx.py`).

## Persyaratan

- PHP ≥ 8.2 (dengan pdo_mysql)
- Composer
- Node ≥ 20
- MySQL (database `ide_segar`, root tanpa sandi — sesuaikan `.env` bila perlu)

## Instalasi

```bash
composer install
npm install
cp .env.example .env        # lalu atur DB_* di .env
php artisan key:generate
php artisan migrate --seed   # membuat DB + mengimpor data backup
php artisan predictions:run  # menjalankan model prediksi pertama
npm run build                # atau npm run dev saat pengembangan
php artisan serve
```

Akses: http://127.0.0.1:8000

**Akun awal:** `admin@idesegarprinting.id` / `password`
(ubah lewat menu Pengaturan; pendaftaran mandiri juga tersedia di `/register`).

## Prediksi — model `linreg-weekly-v1`

- Agregasi qty per produk per minggu (ISO) dari order_items.
- Regresi linier qty ~ indeks minggu per produk (≥ 4 minggu riwayat);
  produk jarang memakai tren kategori (dibagi pangsa), lalu rata-rata global.
- Ekstrapolasi dibatasi maks 4× maksimum mingguan historis produk.
- Evaluasi hold-out 4 minggu terakhir: MAE/RMSE per produk-minggu, MAPE pada
  total mingguan **level toko** (permintaan per produk intermittent — PRD §8).
- Akurasi tampilan = 100 − MAPE (hold-out), dijelaskan di UI.
- Horizon 90 hari; grafik 7/30/90 hari ditarik dari deret harian tersimpan
  (`sales_predictions.series`), bukan dihitung di sisi klien.
- Jadwal: tiap Minggu 06.00 WIB (`bootstrap/app.php` → `predictions:run`).
- Label status: growth ≥ 20% & prediksi ≥ 10 qty → `prioritas_stok`;
  growth ≥ 5% → `pantau`; lainnya `normal`.

Catatan kejujuran data: angka yang tidak tersedia (mis. sisa tagihan DP per
invoice, stok bahan) **tidak dikarang** — ditampilkan sebagai keterangan.

## Halaman

Dashboard · Prediksi Penjualan (banner insight, alur proses sesuai flowchart
sistem usulan, kartu ringkasan, aktual vs prediksi, distribusi kategori, produk
berpotensi) · Data Penjualan / Pesanan (kelola data penjualan historis: tambah,
ubah, hapus, impor CSV) · Produk (+ Kategori) · Pelanggan · Laporan (+ ekspor
CSV) · Notifikasi · Pengaturan.

## Struktur penting

- `app/Services/PredictionService.php` — mesin prediksi & evaluasi
- `app/Console/Commands/RunPredictions.php` — `php artisan predictions:run`
- `database/seed-data/*.json` — katalog & transaksi (dari xlsx)
- `resources/js/Pages/` — halaman Inertia; `Layouts/AppLayout.vue` — shell UI

## Lisensi

MIT (Laravel framework).
