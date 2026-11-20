<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockAdjustmentItem extends Model
{
    use HasFactory;

    protected $table = 'stock_adjustment_item';

    protected $fillable = [
        'adjustment_id',
        'batch_id',
        'obat_id',
        'jumlah',
        'alasan',
        'nilai',
    ];

    protected function casts(): array
    {
        return [
            'jumlah' => 'integer',
            'nilai' => 'decimal:2',
        ];
    }

    public function adjustment(): BelongsTo
    {
        return $this->belongsTo(StockAdjustment::class, 'adjustment_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(BatchObat::class, 'batch_id');
    }

    public function obat(): BelongsTo
    {
        return $this->belongsTo(Obat::class);
    }
}
