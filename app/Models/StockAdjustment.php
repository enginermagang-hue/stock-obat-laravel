<?php

namespace App\Models;

use App\Enums\StatusPenyesuaian;
use App\Enums\TipePenyesuaian;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockAdjustment extends Model
{
    use HasFactory;

    protected $table = 'stock_adjustment';

    protected $fillable = [
        'no_penyesuaian',
        'tipe',
        'tanggal',
        'status',
        'stock_opname_id',
        'user_id',
        'approved_by',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'tipe' => TipePenyesuaian::class,
            'status' => StatusPenyesuaian::class,
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockAdjustmentItem::class, 'adjustment_id');
    }

    public function opname(): BelongsTo
    {
        return $this->belongsTo(StockOpname::class, 'stock_opname_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
