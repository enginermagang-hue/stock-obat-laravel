<?php

use App\Http\Controllers\Anggaran\AnggaranController;
use App\Http\Controllers\Api\V1\MasterResourceController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Distribusi\DistributionController;
use App\Http\Controllers\DistributionSuratJalanController;
use App\Http\Controllers\GlobalSearchController;
use App\Http\Controllers\Laporan\LaporanController;
use App\Http\Controllers\MasterPageController;
use App\Http\Controllers\Pembelian\GoodsReceiptController;
use App\Http\Controllers\Pembelian\PurchaseOrderController;
use App\Http\Controllers\Pengaturan\FacilityLogoController;
use App\Http\Controllers\Pengaturan\KopController;
use App\Http\Controllers\Pengaturan\UserController;
use App\Http\Controllers\Perencanaan\LplpoController;
use App\Http\Controllers\Perencanaan\PermintaanController;
use App\Http\Controllers\Perencanaan\RkoController;
use App\Http\Controllers\Prediksi\PrediksiController;
use App\Http\Controllers\ProfilController;
use App\Http\Controllers\ReportExportController;
use App\Http\Controllers\Stok\AdjustmentController;
use App\Http\Controllers\Stok\BatchController;
use App\Http\Controllers\Stok\LedgerController;
use App\Http\Controllers\Stok\OpnameController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::get('/login', [LoginController::class, 'create'])->name('login');
Route::post('/login', [LoginController::class, 'store'])->name('login.store');
Route::post('/logout', LogoutController::class)->name('logout')->middleware('auth');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/profil', [ProfilController::class, 'edit'])->name('profil');
    Route::put('/profil', [ProfilController::class, 'update'])->name('profil.update');

    Route::get('/pengaturan/pengguna', [UserController::class, 'index'])->middleware('can:manage-users')->name('pengaturan.pengguna');
    Route::put('/pengaturan/pengguna/{user}', [UserController::class, 'update'])->middleware('can:manage-users')->name('pengaturan.pengguna.update')->whereNumber('user');

    Route::get('/pengaturan/kop', [KopController::class, 'edit'])->middleware('can:manage-users')->name('pengaturan.kop');
    Route::put('/pengaturan/kop', [KopController::class, 'update'])->middleware('can:manage-users')->name('pengaturan.kop.update');
    Route::get('/pengaturan/kop/logo', [KopController::class, 'logo'])->middleware('can:manage-users')->name('pengaturan.kop.logo');

    Route::get('/unit/logo', [FacilityLogoController::class, 'edit'])->middleware('can:view-own-data')->name('facility.logo');
    Route::put('/unit/logo', [FacilityLogoController::class, 'update'])->middleware('can:view-own-data')->name('facility.logo.update');
    Route::get('/unit/logo/preview', [FacilityLogoController::class, 'preview'])->middleware('can:view-own-data')->name('facility.logo.preview');

    Route::get('/master/obat', MasterPageController::class)->defaults('resource', 'obat')->name('master.obat');
    Route::get('/master/supplier', MasterPageController::class)->defaults('resource', 'supplier')->name('master.supplier');
    Route::get('/master/sumber-dana', MasterPageController::class)->defaults('resource', 'sumber-dana')->name('master.sumber-dana');
    Route::get('/master/facilities', MasterPageController::class)->defaults('resource', 'facilities')->name('master.facilities');
    Route::get('/master/licenses', MasterPageController::class)->defaults('resource', 'licenses')->name('master.licenses');

    Route::get('/search', GlobalSearchController::class)->name('search');

    Route::prefix('api/v1/master')->name('api.master.')->group(function () {
        Route::get('/{resource}', [MasterResourceController::class, 'index']);
        Route::post('/{resource}', [MasterResourceController::class, 'store']);
        Route::put('/{resource}/{id}', [MasterResourceController::class, 'update'])->whereNumber('id');
        Route::delete('/{resource}/{id}', [MasterResourceController::class, 'destroy'])->whereNumber('id');
    });

    Route::get('/stok/batch', BatchController::class)->middleware('can:manage-stock')->name('stok.batch');
    Route::get('/stok/ledger', LedgerController::class)->middleware('can:manage-stock')->name('stok.ledger');

    Route::get('/stok/penyesuaian', [AdjustmentController::class, 'index'])->middleware('can:manage-stock')->name('stok.adjustment');
    Route::post('/stok/penyesuaian', [AdjustmentController::class, 'store'])->middleware('can:manage-stock')->name('stok.adjustment.store');
    Route::post('/stok/penyesuaian/{adjustment}/process', [AdjustmentController::class, 'process'])->middleware('can:manage-stock')->name('stok.adjustment.process');
    Route::post('/stok/penyesuaian/{adjustment}/cancel', [AdjustmentController::class, 'cancel'])->middleware('can:manage-stock')->name('stok.adjustment.cancel');

    Route::get('/stok/opname', [OpnameController::class, 'index'])->middleware('can:input-opname')->name('stok.opname');
    Route::post('/stok/opname', [OpnameController::class, 'store'])->middleware('can:input-opname')->name('stok.opname.store');
    Route::post('/stok/opname/{opname}/fisik', [OpnameController::class, 'fisik'])->middleware('can:input-opname')->name('stok.opname.fisik');
    Route::post('/stok/opname/{opname}/verify', [OpnameController::class, 'verify'])->middleware('can:input-opname')->name('stok.opname.verify');
    Route::post('/stok/opname/{opname}/approve', [OpnameController::class, 'approve'])->middleware('can:input-opname')->name('stok.opname.approve');

    Route::get('/pembelian/purchase-order', [PurchaseOrderController::class, 'index'])->middleware('can:receive-goods')->name('pembelian.purchase-order');
    Route::get('/pembelian/purchase-order/create', [PurchaseOrderController::class, 'create'])->middleware('can:receive-goods')->name('pembelian.purchase-order.create');
    Route::post('/pembelian/purchase-order', [PurchaseOrderController::class, 'store'])->middleware('can:receive-goods')->name('pembelian.purchase-order.store');
    Route::get('/pembelian/purchase-order/{purchaseOrder}', [PurchaseOrderController::class, 'show'])->middleware('can:receive-goods')->name('pembelian.purchase-order.show')->whereNumber('purchaseOrder');
    Route::get('/pembelian/purchase-order/{purchaseOrder}/edit', [PurchaseOrderController::class, 'edit'])->middleware('can:receive-goods')->name('pembelian.purchase-order.edit')->whereNumber('purchaseOrder');
    Route::put('/pembelian/purchase-order/{purchaseOrder}', [PurchaseOrderController::class, 'update'])->middleware('can:receive-goods')->name('pembelian.purchase-order.update')->whereNumber('purchaseOrder');
    Route::post('/pembelian/purchase-order/{purchaseOrder}/send', [PurchaseOrderController::class, 'send'])->middleware('can:receive-goods')->name('pembelian.purchase-order.send');
    Route::post('/pembelian/purchase-order/{purchaseOrder}/approve', [PurchaseOrderController::class, 'approve'])->middleware('can:receive-goods')->name('pembelian.purchase-order.approve');
    Route::post('/pembelian/purchase-order/{purchaseOrder}/reject', [PurchaseOrderController::class, 'reject'])->middleware('can:receive-goods')->name('pembelian.purchase-order.reject');
    Route::post('/pembelian/purchase-order/{purchaseOrder}/cancel', [PurchaseOrderController::class, 'cancel'])->middleware('can:receive-goods')->name('pembelian.purchase-order.cancel');

    Route::get('/pembelian/penerimaan', [GoodsReceiptController::class, 'index'])->middleware('can:receive-goods')->name('pembelian.penerimaan');
    Route::get('/pembelian/penerimaan/create', [GoodsReceiptController::class, 'create'])->middleware('can:receive-goods')->name('pembelian.penerimaan.create');
    Route::post('/pembelian/penerimaan', [GoodsReceiptController::class, 'store'])->middleware('can:receive-goods')->name('pembelian.penerimaan.store');
    Route::get('/pembelian/penerimaan/{penerimaan}', [GoodsReceiptController::class, 'show'])->middleware('can:receive-goods')->name('pembelian.penerimaan.show')->whereNumber('penerimaan');
    Route::get('/pembelian/penerimaan/{penerimaan}/edit', [GoodsReceiptController::class, 'edit'])->middleware('can:receive-goods')->name('pembelian.penerimaan.edit')->whereNumber('penerimaan');
    Route::put('/pembelian/penerimaan/{penerimaan}', [GoodsReceiptController::class, 'update'])->middleware('can:receive-goods')->name('pembelian.penerimaan.update')->whereNumber('penerimaan');

    Route::get('/lplpo', [LplpoController::class, 'index'])->middleware('can:view-lplpo')->name('lplpo.index');
    Route::get('/lplpo/create', [LplpoController::class, 'create'])->middleware('can:create-lplpo')->name('lplpo.create');
    Route::get('/lplpo/item-lookup', [LplpoController::class, 'itemLookup'])->middleware('can:create-lplpo')->name('lplpo.item-lookup');
    Route::post('/lplpo', [LplpoController::class, 'store'])->middleware('can:create-lplpo')->name('lplpo.store');
    Route::get('/lplpo/{lplpo}', [LplpoController::class, 'show'])->middleware('can:view-lplpo')->name('lplpo.show')->whereNumber('lplpo');
    Route::get('/lplpo/{lplpo}/edit', [LplpoController::class, 'edit'])->middleware('can:create-lplpo')->name('lplpo.edit')->whereNumber('lplpo');
    Route::put('/lplpo/{lplpo}', [LplpoController::class, 'update'])->middleware('can:create-lplpo')->name('lplpo.update')->whereNumber('lplpo');
    Route::post('/lplpo/{lplpo}/submit', [LplpoController::class, 'submit'])->middleware('can:create-lplpo')->name('lplpo.submit');
    Route::post('/lplpo/{lplpo}/verify', [LplpoController::class, 'verify'])->middleware('can:verify-lplpo')->name('lplpo.verify');
    Route::post('/lplpo/{lplpo}/permintaan', [LplpoController::class, 'createPermintaan'])->middleware('can:create-permintaan')->name('lplpo.permintaan');

    Route::get('/permintaan', [PermintaanController::class, 'index'])->middleware('can:view-permintaan')->name('permintaan.index');
    Route::get('/permintaan/create', [PermintaanController::class, 'create'])->middleware('can:create-permintaan')->name('permintaan.create');
    Route::post('/permintaan', [PermintaanController::class, 'store'])->middleware('can:create-permintaan')->name('permintaan.store');
    Route::get('/permintaan/{permintaan}', [PermintaanController::class, 'show'])->middleware('can:view-permintaan')->name('permintaan.show')->whereNumber('permintaan');
    Route::get('/permintaan/{permintaan}/edit', [PermintaanController::class, 'edit'])->middleware('can:create-permintaan')->name('permintaan.edit')->whereNumber('permintaan');
    Route::put('/permintaan/{permintaan}', [PermintaanController::class, 'update'])->middleware('can:create-permintaan')->name('permintaan.update')->whereNumber('permintaan');
    Route::post('/permintaan/{permintaan}/submit', [PermintaanController::class, 'submit'])->middleware('can:create-permintaan')->name('permintaan.submit');
    Route::post('/permintaan/{permintaan}/verify', [PermintaanController::class, 'verify'])->middleware('can:verify-permintaan')->name('permintaan.verify');

    Route::get('/distribusi', [DistributionController::class, 'index'])->middleware('can:view-distribution')->name('distribusi.index');
    Route::get('/distribusi/create', [DistributionController::class, 'create'])->middleware('can:create-distribution')->name('distribusi.create');
    Route::get('/distribusi/preview', [DistributionController::class, 'preview'])->middleware('can:create-distribution')->name('distribusi.preview');
    Route::post('/distribusi', [DistributionController::class, 'store'])->middleware('can:create-distribution')->name('distribusi.store');
    Route::get('/distribusi/{distribution}', [DistributionController::class, 'show'])->middleware('can:view-distribution')->name('distribusi.show')->whereNumber('distribution');
    Route::get('/distribusi/{distribution}/edit', [DistributionController::class, 'edit'])->middleware('can:create-distribution')->name('distribusi.edit')->whereNumber('distribution');
    Route::put('/distribusi/{distribution}', [DistributionController::class, 'update'])->middleware('can:create-distribution')->name('distribusi.update')->whereNumber('distribution');
    Route::post('/distribusi/{distribution}/approve', [DistributionController::class, 'approve'])->middleware('can:approve-distribution')->name('distribusi.approve');
    Route::post('/distribusi/{distribution}/send', [DistributionController::class, 'send'])->middleware('can:create-distribution')->name('distribusi.send');
    Route::post('/distribusi/{distribution}/confirm', [DistributionController::class, 'confirm'])->middleware('can:confirm-distribution')->name('distribusi.confirm');
    Route::get('/distribusi/{distribution}/tanda-terima', [DistributionController::class, 'tandaTerima'])->middleware('can:view-distribution')->name('distribusi.tanda-terima')->whereNumber('distribution');
    Route::post('/distribusi/{distribution}/cancel', [DistributionController::class, 'cancel'])->middleware('can:approve-distribution')->name('distribusi.cancel');

    Route::get('/distribusi/{distribution}/surat-jalan', DistributionSuratJalanController::class)
        ->middleware('can:view-distribution')
        ->name('distribusi.surat-jalan');

    Route::get('/rko', [RkoController::class, 'index'])->middleware('can:manage-rko')->name('rko.index');
    Route::post('/rko', [RkoController::class, 'build'])->middleware('can:manage-rko')->name('rko.build');
    Route::post('/rko/{rko}/approve', [RkoController::class, 'approve'])->middleware('can:manage-rko')->name('rko.approve');
    Route::post('/rko/{rko}/purchase-order', [RkoController::class, 'generatePo'])->middleware('can:approve-po')->name('rko.purchase-order');

    Route::get('/prediksi', [PrediksiController::class, 'index'])->middleware('can:manage-prediksi')->name('prediksi.index');
    Route::post('/prediksi', [PrediksiController::class, 'run'])->middleware('can:manage-prediksi')->name('prediksi.run');
    Route::post('/prediksi/{prediksi}/validasi', [PrediksiController::class, 'validate'])->middleware('can:manage-prediksi')->name('prediksi.validate');

    Route::get('/anggaran', [AnggaranController::class, 'index'])->middleware('can:manage-anggaran')->name('anggaran.index');
    Route::post('/anggaran', [AnggaranController::class, 'store'])->middleware('can:manage-anggaran')->name('anggaran.store');
    Route::delete('/anggaran/{anggaran}', [AnggaranController::class, 'destroy'])->middleware('can:manage-anggaran')->name('anggaran.destroy');

    Route::get('/laporan', [LaporanController::class, 'index'])->middleware('can:view-reports')->name('laporan.index');
    Route::get('/laporan/export/{report}', ReportExportController::class)->middleware('can:view-reports')->name('laporan.export');
    Route::get('/audit-log', [LaporanController::class, 'index'])->middleware('can:view-audit-log')->name('audit-log.index');
});
