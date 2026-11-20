<?php

namespace App\Models;

use App\Enums\StatusPurchaseOrder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $table = 'purchase_order';

    protected $fillable = [
        'no_po',
        'supplier_id',
        'sumber_dana_id',
        'no_dokumen_anggaran',
        'tanggal_po',
        'status',
        'total',
        'user_id',
        'alasan_override_pagu',
        'approved_by',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_po' => 'date',
            'status' => StatusPurchaseOrder::class,
            'total' => 'decimal:2',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function sumberDana(): BelongsTo
    {
        return $this->belongsTo(SumberDana::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class, 'po_id');
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(GoodsReceipt::class, 'po_id');
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
