<?php

namespace App\Models;

use App\Enums\ModeOpname;
use App\Enums\StatusOpname;
use App\Enums\TipeOpname;
use App\Scopes\FacilityScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockOpname extends Model
{
    use HasFactory;

    protected $table = 'stock_opname';

    protected $fillable = [
        'no_opname',
        'tanggal',
        'periode_bulan',
        'periode_tahun',
        'tipe',
        'mode',
        'facility_id',
        'status',
        'petugas_id',
        'verified_by',
        'disetujui_oleh',
        'catatan',
        'versi',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'tipe' => TipeOpname::class,
            'mode' => ModeOpname::class,
            'status' => StatusOpname::class,
            'periode_bulan' => 'integer',
            'periode_tahun' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new FacilityScope);
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockOpnameItem::class, 'opname_id');
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function petugas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'petugas_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disetujui_oleh');
    }
}
