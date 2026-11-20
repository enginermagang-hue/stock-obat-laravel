<?php

namespace App\Models;

use App\Enums\StatusLplpo;
use App\Scopes\FacilityScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lplpo extends Model
{
    use HasFactory;

    protected $table = 'lplpo';

    protected $fillable = [
        'no_lplpo',
        'facility_id',
        'bulan',
        'tahun',
        'tanggal_penyerahan',
        'status',
        'versi',
        'verified_by',
        'verified_at',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_penyerahan' => 'date',
            'verified_at' => 'datetime',
            'status' => StatusLplpo::class,
            'bulan' => 'integer',
            'tahun' => 'integer',
            'versi' => 'integer',
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

    public function items(): HasMany
    {
        return $this->hasMany(LplpoItem::class, 'lplpo_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function permintaan(): HasMany
    {
        return $this->hasMany(PermintaanObat::class, 'lplpo_id');
    }

    public function isImmutable(): bool
    {
        return $this->status === StatusLplpo::Diverifikasi;
    }
}
