<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockOpnameItem extends Model
{
    use HasFactory;

    protected $table = 'stock_opname_item';

    protected $fillable = [
        'opname_id',
        'batch_id',
        'stok_sistem',
        'stok_fisik',
        'selisih',
        'harga_beli',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'stok_sistem' => 'integer',
            'stok_fisik' => 'integer',
            'selisih' => 'integer',
            'harga_beli' => 'decimal:2',
        ];
    }

    public function opname(): BelongsTo
    {
        return $this->belongsTo(StockOpname::class, 'opname_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(BatchObat::class, 'batch_id');
    }
}
