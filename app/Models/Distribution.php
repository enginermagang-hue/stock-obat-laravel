<?php

namespace App\Models;

use App\Enums\StatusDistribusi;
use App\Scopes\FacilityScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Distribution extends Model
{
    use HasFactory;

    protected $table = 'distribution';

    protected $fillable = [
        'no_surat',
        'no_sbbk',
        'facility_id',
        'permintaan_id',
        'tanggal',
        'tanggal_keluar',
        'status',
        'pengirim_id',
        'penerima_nama',
        'penerima_nip',
        'penerima_jabatan',
        'penerima_unit',
        'no_bast',
        'tanggal_diterima',
        'file_tanda_terima',
        'catatan',
        'user_id',
        'approved_by',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'tanggal_keluar' => 'date',
            'tanggal_diterima' => 'date',
            'status' => StatusDistribusi::class,
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new FacilityScope);
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function permintaan(): BelongsTo
    {
        return $this->belongsTo(PermintaanObat::class, 'permintaan_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(DistributionItem::class, 'distribution_id');
    }

    public function pengirim(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pengirim_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function total(): float
    {
        return (float) $this->items->sum('subtotal');
    }
}
