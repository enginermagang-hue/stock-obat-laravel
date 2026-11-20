<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BatchObat extends Model
{
    use HasFactory;

    protected $table = 'batch_obat';

    protected $fillable = [
        'obat_id',
        'no_batch',
        'tanggal_produksi',
        'tanggal_kedaluwarsa',
        'jumlah_awal',
        'jumlah_sisa',
        'harga_beli',
        'lokasi_rak',
        'sumber_dana_id',
        'supplier_id',
        'aktif',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_produksi' => 'date',
            'tanggal_kedaluwarsa' => 'date',
            'jumlah_awal' => 'integer',
            'jumlah_sisa' => 'integer',
            'harga_beli' => 'decimal:2',
            'aktif' => 'boolean',
        ];
    }

    public function obat(): BelongsTo
    {
        return $this->belongsTo(Obat::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function sumberDana(): BelongsTo
    {
        return $this->belongsTo(SumberDana::class);
    }

    public function ledger(): HasMany
    {
        return $this->hasMany(StockLedger::class, 'batch_id');
    }

    public function scopeFefo(Builder $query): void
    {
        $query->where('jumlah_sisa', '>', 0)
            ->whereDate('tanggal_kedaluwarsa', '>', now())
            ->orderBy('tanggal_kedaluwarsa')
            ->orderBy('id');
    }

    public function scopeExpiring(Builder $query, int $days = 90): void
    {
        $query->whereDate('tanggal_kedaluwarsa', '<=', now()->addDays($days))
            ->whereDate('tanggal_kedaluwarsa', '>=', now());
    }
}
