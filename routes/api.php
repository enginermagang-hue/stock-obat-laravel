<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BatchObatController;
use App\Http\Controllers\Api\V1\LplpoController;
use App\Http\Controllers\Api\V1\PermintaanController;
use App\Http\Controllers\Api\V1\PrediksiController;
use App\Http\Controllers\Api\V1\PurchaseOrderController;
use App\Http\Controllers\Api\V1\RkoController;
use App\Http\Controllers\Api\V1\StockAdjustmentController;
use App\Http\Controllers\Api\V1\StockLedgerController;
use App\Http\Controllers\Api\V1\StockOpnameController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        Route::get('/batch-obat', [BatchObatController::class, 'index']);
        Route::get('/batch-obat/{batch}', [BatchObatController::class, 'show']);
        Route::get('/stock-ledger', [StockLedgerController::class, 'index']);

        Route::get('/purchase-orders', [PurchaseOrderController::class, 'index']);
        Route::post('/purchase-orders', [PurchaseOrderController::class, 'store']);
        Route::post('/purchase-orders/{purchaseOrder}/approve', [PurchaseOrderController::class, 'approve']);
        Route::post('/purchase-orders/{purchaseOrder}/receive', [PurchaseOrderController::class, 'receive']);

        Route::get('/stock-adjustments', [StockAdjustmentController::class, 'index']);
        Route::post('/stock-adjustments', [StockAdjustmentController::class, 'store']);
        Route::post('/stock-adjustments/{stockAdjustment}/process', [StockAdjustmentController::class, 'process']);

        Route::get('/opname', [StockOpnameController::class, 'index']);
        Route::post('/opname', [StockOpnameController::class, 'store']);
        Route::put('/opname/{opname}/stok-fisik', [StockOpnameController::class, 'inputFisik']);
        Route::post('/opname/{opname}/verify', [StockOpnameController::class, 'verify']);
        Route::post('/opname/{opname}/approve', [StockOpnameController::class, 'approve']);

        Route::get('/prediksi', [PrediksiController::class, 'index']);
        Route::post('/prediksi', [PrediksiController::class, 'store']);
        Route::get('/prediksi/akurasi', [PrediksiController::class, 'akurasi']);
        Route::get('/prediksi/{prediksi}', [PrediksiController::class, 'show']);
        Route::post('/prediksi/{prediksi}/validasi', [PrediksiController::class, 'validatePrediction']);

        Route::get('/rko', [RkoController::class, 'index']);
        Route::post('/rko', [RkoController::class, 'store']);
        Route::get('/rko/{rko}', [RkoController::class, 'show']);
        Route::post('/rko/{rko}/approve', [RkoController::class, 'approve']);
        Route::post('/rko/{rko}/purchase-order', [RkoController::class, 'purchaseOrder']);

        Route::get('/lplpo', [LplpoController::class, 'index']);
        Route::post('/lplpo', [LplpoController::class, 'store']);
        Route::get('/lplpo/{lplpo}', [LplpoController::class, 'show']);
        Route::post('/lplpo/{lplpo}/submit', [LplpoController::class, 'submit']);
        Route::post('/lplpo/{lplpo}/verify', [LplpoController::class, 'verify']);
        Route::post('/lplpo/{lplpo}/permintaan', [LplpoController::class, 'createPermintaan']);

        Route::get('/permintaan', [PermintaanController::class, 'index']);
        Route::post('/permintaan', [PermintaanController::class, 'store']);
        Route::get('/permintaan/{permintaan}', [PermintaanController::class, 'show']);
        Route::post('/permintaan/{permintaan}/submit', [PermintaanController::class, 'submit']);
        Route::post('/permintaan/{permintaan}/verify', [PermintaanController::class, 'verify']);
    });
});
