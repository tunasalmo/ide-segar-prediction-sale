<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PredictionController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SettingController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/prediksi', [PredictionController::class, 'index'])->name('prediksi');
    Route::post('/prediksi/hitung', [PredictionController::class, 'calculate'])->name('prediksi.hitung');

    Route::get('/produk', [ProductController::class, 'index'])->name('produk.index');
    Route::get('/produk/{product}', [ProductController::class, 'show'])->name('produk.show');
    Route::post('/produk', [ProductController::class, 'store'])->name('produk.store');
    Route::put('/produk/{product}', [ProductController::class, 'update'])->name('produk.update');
    Route::delete('/produk/{product}', [ProductController::class, 'destroy'])->name('produk.destroy');
    Route::post('/produk/{product}/aktifkan', [ProductController::class, 'activate'])->name('produk.activate');

    Route::get('/kategori', [CategoryController::class, 'index'])->name('kategori.index');
    Route::post('/kategori', [CategoryController::class, 'store'])->name('kategori.store');
    Route::put('/kategori/{category}', [CategoryController::class, 'update'])->name('kategori.update');
    Route::delete('/kategori/{category}', [CategoryController::class, 'destroy'])->name('kategori.destroy');

    Route::get('/pesanan', [OrderController::class, 'index'])->name('pesanan.index');
    Route::get('/pesanan/import/template', [OrderController::class, 'importTemplate'])->name('pesanan.import.template');
    Route::post('/pesanan/import', [OrderController::class, 'import'])->name('pesanan.import');
    Route::post('/pesanan', [OrderController::class, 'store'])->name('pesanan.store');
    Route::put('/pesanan/{order}', [OrderController::class, 'update'])->name('pesanan.update');
    Route::delete('/pesanan/{order}', [OrderController::class, 'destroy'])->name('pesanan.destroy');
    Route::get('/pesanan/{order}', [OrderController::class, 'show'])->name('pesanan.show');
    Route::put('/pesanan/{order}/status', [OrderController::class, 'updateStatus'])->name('pesanan.status');

    Route::get('/pelanggan', [CustomerController::class, 'index'])->name('pelanggan.index');
    Route::get('/pelanggan/{customer}', [CustomerController::class, 'show'])->name('pelanggan.show');
    Route::post('/pelanggan', [CustomerController::class, 'store'])->name('pelanggan.store');
    Route::put('/pelanggan/{customer}', [CustomerController::class, 'update'])->name('pelanggan.update');

    Route::get('/laporan', [ReportController::class, 'index'])->name('laporan.index');
    Route::get('/laporan/export', [ReportController::class, 'export'])->name('laporan.export');

    Route::get('/pengaturan', [SettingController::class, 'index'])->name('pengaturan.index');
    Route::put('/pengaturan/profil', [SettingController::class, 'updateProfile'])->name('pengaturan.profil');
    Route::put('/pengaturan/sandi', [SettingController::class, 'updatePassword'])->name('pengaturan.sandi');

    Route::get('/notifikasi', [NotificationController::class, 'index'])->name('notifikasi.index');
    Route::post('/notifikasi/{notification}/baca', [NotificationController::class, 'markRead'])->name('notifikasi.baca');
    Route::post('/notifikasi/baca-semua', [NotificationController::class, 'markAllRead'])->name('notifikasi.baca-semua');

    Route::get('/api/search', [SearchController::class, 'index'])->name('search');
});

require __DIR__.'/auth.php';
