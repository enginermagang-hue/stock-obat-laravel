<?php

namespace App\Models;

use App\Enums\TipeMutasi;
use App\Enums\TipeReferensi;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockLedger extends Model
{
    use HasFactory;

    protected $table = 'stock_ledger';

    public $timestamps = false;

    protected $fillable = [
        'batch_id',
        'tipe',
        'jumlah',
        'jumlah_sebelum',
        'jumlah_sesudah',
        'tipe_referensi',
        'referensi_id',
        'tanggal_transaksi',
        'user_id',
        'catatan',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'tipe' => TipeMutasi::class,
            'tipe_referensi' => TipeReferensi::class,
            'tanggal_transaksi' => 'datetime',
            'created_at' => 'datetime',
            'jumlah' => 'integer',
            'jumlah_sebelum' => 'integer',
            'jumlah_sesudah' => 'integer',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(BatchObat::class, 'batch_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
