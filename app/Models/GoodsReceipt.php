<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GoodsReceipt extends Model
{
    use HasFactory;

    protected $table = 'goods_receipt';

    protected $fillable = [
        'no_penerimaan',
        'po_id',
        'supplier_id',
        'sumber_dana_id',
        'tanggal_terima',
        'user_id',
        'catatan',
    ];

    protected function casts(): array
    {
        return ['tanggal_terima' => 'date'];
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'po_id');
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
        return $this->hasMany(GoodsReceiptItem::class, 'receipt_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
