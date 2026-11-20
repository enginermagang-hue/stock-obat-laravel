<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoodsReceiptItem extends Model
{
    use HasFactory;

    protected $table = 'goods_receipt_item';

    protected $fillable = [
        'receipt_id',
        'po_item_id',
        'obat_id',
        'no_batch',
        'tanggal_produksi',
        'tanggal_kedaluwarsa',
        'jumlah',
        'harga_beli',
        'lokasi_rak',
        'sumber_dana_id',
        'subtotal',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_produksi' => 'date',
            'tanggal_kedaluwarsa' => 'date',
            'jumlah' => 'integer',
            'harga_beli' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class, 'receipt_id');
    }

    public function poItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class, 'po_item_id');
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
