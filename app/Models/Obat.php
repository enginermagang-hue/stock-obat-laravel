<?php

namespace App\Models;

use App\Enums\BentukSediaan;
use App\Enums\GolonganObat;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Obat extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'obat';

    protected $fillable = [
        'kode_obat',
        'nama',
        'nama_generik',
        'kategori_id',
        'bentuk_sediaan',
        'kekuatan',
        'satuan',
        'golongan',
        'no_izin_edar',
        'produsen',
        'harga_satuan_terakhir',
        'stok_minimum',
        'butuh_cold_chain',
        'aktif',
    ];

    protected function casts(): array
    {
        return [
            'bentuk_sediaan' => BentukSediaan::class,
            'golongan' => GolonganObat::class,
            'harga_satuan_terakhir' => 'decimal:2',
            'stok_minimum' => 'integer',
            'butuh_cold_chain' => 'boolean',
            'aktif' => 'boolean',
            'deleted_at' => 'datetime',
        ];
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(KategoriObat::class, 'kategori_id');
    }

    public function batches(): HasMany
    {
        return $this->hasMany(BatchObat::class);
    }
}
