<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RkoItem extends Model
{
    use HasFactory;

    protected $table = 'rko_item';

    protected $fillable = [
        'rko_id',
        'obat_id',
        'sumber_dana_id',
        'pemakaian_rata_rata',
        'estimasi_pemakaian',
        'stok_akhir',
        'stok_dalam_pesanan',
        'stok_pengaman',
        'kebutuhan_bersih',
        'harga_estimasi',
        'total_estimasi',
        'sumber',
        'catatan',
        'prediksi_id',
        'prediksi_ai',
        'prediksi_interval_bawah',
        'prediksi_interval_atas',
        'prediksi_confidence',
        'alasan_pilihan',
    ];

    protected function casts(): array
    {
        return [
            'pemakaian_rata_rata' => 'decimal:2',
            'estimasi_pemakaian' => 'decimal:2',
            'stok_akhir' => 'integer',
            'stok_dalam_pesanan' => 'integer',
            'stok_pengaman' => 'decimal:2',
            'kebutuhan_bersih' => 'decimal:2',
            'harga_estimasi' => 'decimal:2',
            'total_estimasi' => 'decimal:2',
            'prediksi_ai' => 'decimal:2',
            'prediksi_interval_bawah' => 'decimal:2',
            'prediksi_interval_atas' => 'decimal:2',
            'prediksi_confidence' => 'decimal:4',
        ];
    }

    public function rko(): BelongsTo
    {
        return $this->belongsTo(Rko::class, 'rko_id');
    }

    public function obat(): BelongsTo
    {
        return $this->belongsTo(Obat::class);
    }

    public function sumberDana(): BelongsTo
    {
        return $this->belongsTo(SumberDana::class);
    }
}
