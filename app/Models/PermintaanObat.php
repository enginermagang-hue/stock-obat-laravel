<?php

namespace App\Models;

use App\Enums\SumberPermintaan;
use App\Enums\StatusPermintaan;
use App\Scopes\FacilityScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PermintaanObat extends Model
{
    use HasFactory;

    protected $table = 'permintaan_obat';

    protected $fillable = [
        'no_permintaan',
        'facility_id',
        'lplpo_id',
        'sumber',
        'periode_awal',
        'periode_akhir',
        'tanggal',
        'status',
        'verified_by',
        'verified_at',
        'catatan_verifikasi',
    ];

    protected function casts(): array
    {
        return [
            'sumber' => SumberPermintaan::class,
            'status' => StatusPermintaan::class,
            'periode_awal' => 'date',
            'periode_akhir' => 'date',
            'tanggal' => 'date',
            'verified_at' => 'datetime',
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

    public function lplpo(): BelongsTo
    {
        return $this->belongsTo(Lplpo::class, 'lplpo_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PermintaanObatItem::class, 'permintaan_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
