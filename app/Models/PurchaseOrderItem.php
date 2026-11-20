<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderItem extends Model
{
    use HasFactory;

    protected $table = 'purchase_order_item';

    protected $fillable = [
        'po_id',
        'obat_id',
        'jumlah',
        'harga_beli',
        'no_batch',
        'tanggal_kedaluwarsa',
        'jumlah_diterima',
        'subtotal',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_kedaluwarsa' => 'date',
            'jumlah' => 'integer',
            'jumlah_diterima' => 'integer',
            'harga_beli' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'po_id');
    }

    public function obat(): BelongsTo
    {
        return $this->belongsTo(Obat::class);
    }
}
