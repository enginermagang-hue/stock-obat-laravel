<?php

namespace App\Models;

use App\Enums\MetodeRko;
use App\Enums\StatusRko;
use App\Enums\TingkatRko;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rko extends Model
{
    use HasFactory;

    protected $table = 'rko';

    protected $fillable = [
        'no_rko',
        'tahun',
        'periode',
        'tingkat',
        'metode',
        'lead_time_bulan',
        'faktor_pengaman',
        'bobot_ai',
        'sumber_dana_id',
        'supplier_id',
        'status',
        'versi',
        'disusun_oleh',
        'disetujui_oleh',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'tingkat' => TingkatRko::class,
            'metode' => MetodeRko::class,
            'status' => StatusRko::class,
            'tahun' => 'integer',
            'periode' => 'integer',
            'versi' => 'integer',
            'lead_time_bulan' => 'integer',
            'faktor_pengaman' => 'decimal:2',
            'bobot_ai' => 'decimal:3',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(RkoItem::class, 'rko_id');
    }

    public function sumberDana(): BelongsTo
    {
        return $this->belongsTo(SumberDana::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function penyusun(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disusun_oleh');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disetujui_oleh');
    }
}
