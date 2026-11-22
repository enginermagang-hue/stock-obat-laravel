<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DistributionItem extends Model
{
    use HasFactory;

    protected $table = 'distribution_item';

    protected $fillable = [
        'distribution_id',
        'permintaan_item_id',
        'obat_id',
        'batch_id',
        'jumlah',
        'harga_satuan',
        'subtotal',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'jumlah' => 'integer',
            'harga_satuan' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    public function distribution(): BelongsTo
    {
        return $this->belongsTo(Distribution::class, 'distribution_id');
    }

    public function permintaanItem(): BelongsTo
    {
        return $this->belongsTo(PermintaanObatItem::class, 'permintaan_item_id');
    }

    public function obat(): BelongsTo
    {
        return $this->belongsTo(Obat::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(BatchObat::class, 'batch_id');
    }
}
