<?php

namespace App\Models;

use App\Enums\StatusItemPermintaan;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PermintaanObatItem extends Model
{
    use HasFactory;

    protected $table = 'permintaan_obat_item';

    protected $fillable = [
        'permintaan_id',
        'obat_id',
        'jumlah_diminta',
        'jumlah_disetujui',
        'status_item',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'jumlah_diminta' => 'integer',
            'jumlah_disetujui' => 'integer',
            'status_item' => StatusItemPermintaan::class,
        ];
    }

    public function permintaan(): BelongsTo
    {
        return $this->belongsTo(PermintaanObat::class, 'permintaan_id');
    }

    public function obat(): BelongsTo
    {
        return $this->belongsTo(Obat::class);
    }
}
