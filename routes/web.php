<?php

use App\Http\Controllers\Auth\LogoutController;
use App\Livewire\Dashboard;
use App\Livewire\Login;
use App\Livewire\Master\FacilityIndex;
use App\Livewire\Master\ObatIndex;
use App\Livewire\Master\SumberDanaIndex;
use App\Livewire\Master\SupplierIndex;
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
});
