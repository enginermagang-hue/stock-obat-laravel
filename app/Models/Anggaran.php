<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Anggaran extends Model
{
    use HasFactory;

    /** Sentinel unit RS karena unique index tidak boleh memakai NULL. */
    public const RS_FACILITY_ID = 0;

    protected $table = 'anggaran';

    protected $fillable = [
        'sumber_dana_id',
        'tahun',
        'facility_id',
        'jumlah_anggaran',
        'status',
        'versi',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'tahun' => 'integer',
            'facility_id' => 'integer',
            'jumlah_anggaran' => 'decimal:2',
            'versi' => 'integer',
        ];
    }

    public function sumberDana(): BelongsTo
    {
        return $this->belongsTo(SumberDana::class);
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function scopeTahun(Builder $query, int $tahun): Builder
    {
        return $query->where('tahun', $tahun);
    }

    public function scopeUnit(Builder $query, int $facilityId): Builder
    {
        return $query->where('facility_id', $facilityId);
    }

    public function isRs(): bool
    {
        return $this->facility_id === self::RS_FACILITY_ID;
    }

    public function unitLabel(): string
    {
        return $this->isRs() ? 'Rumah Sakit' : ($this->facility?->nama ?? 'Unit');
    }
}
