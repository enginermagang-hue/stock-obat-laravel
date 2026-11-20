<?php

use App\Http\Controllers\Auth\LogoutController;
use App\Livewire\Dashboard;
use App\Livewire\Login;
use App\Livewire\Master\FacilityIndex;
use App\Livewire\Master\ObatIndex;
use App\Livewire\Master\SumberDanaIndex;
use App\Livewire\Master\SupplierIndex;
use App\Livewire\Pembelian\GoodsReceiptForm;
use App\Livewire\Pembelian\PurchaseOrderIndex;
use App\Livewire\Stok\AdjustmentIndex;
use App\Livewire\Stok\BatchIndex;
use App\Livewire\Stok\LedgerIndex;
use App\Livewire\Stok\OpnameIndex;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect('/dashboard'));

Route::get('/login', Login::class)->name('login');
Route::post('/logout', LogoutController::class)->name('logout')->middleware('auth');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', Dashboard::class)->name('dashboard');

    Route::get('/master/obat', ObatIndex::class)->middleware('can:viewAny,App\Models\Obat')->name('master.obat');
    Route::get('/master/supplier', SupplierIndex::class)->middleware('can:viewAny,App\Models\Supplier')->name('master.supplier');
    Route::get('/master/sumber-dana', SumberDanaIndex::class)->middleware('can:viewAny,App\Models\SumberDana')->name('master.sumber-dana');
    Route::get('/master/facilities', FacilityIndex::class)->middleware('can:viewAny,App\Models\Facility')->name('master.facilities');

    Route::get('/stok/batch', BatchIndex::class)->middleware('can:manage-stock')->name('stok.batch');
    Route::get('/stok/ledger', LedgerIndex::class)->middleware('can:manage-stock')->name('stok.ledger');
    Route::get('/stok/penyesuaian', AdjustmentIndex::class)->middleware('can:manage-stock')->name('stok.adjustment');
    Route::get('/stok/opname', OpnameIndex::class)->middleware('can:input-opname')->name('stok.opname');

    Route::get('/pembelian/purchase-order', PurchaseOrderIndex::class)->middleware('can:receive-goods')->name('pembelian.purchase-order');
    Route::get('/pembelian/penerimaan', GoodsReceiptForm::class)->middleware('can:receive-goods')->name('pembelian.penerimaan');
});
