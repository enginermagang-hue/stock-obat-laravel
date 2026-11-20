<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LplpoItem extends Model
{
    use HasFactory;

    protected $table = 'lplpo_item';

    protected $fillable = [
        'lplpo_id',
        'obat_id',
        'stok_awal',
        'pemasukan',
        'pemakaian',
        'stok_akhir',
        'stok_akhir_sistem',
        'stok_minimum',
        'jumlah_diminta',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'stok_awal' => 'integer',
            'pemasukan' => 'integer',
            'pemakaian' => 'integer',
            'stok_akhir' => 'integer',
            'stok_akhir_sistem' => 'integer',
            'stok_minimum' => 'integer',
            'jumlah_diminta' => 'integer',
        ];
    }

    public function lplpo(): BelongsTo
    {
        return $this->belongsTo(Lplpo::class, 'lplpo_id');
    }

    public function obat(): BelongsTo
    {
        return $this->belongsTo(Obat::class);
    }
}
