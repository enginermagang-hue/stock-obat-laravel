<?php

namespace App\Models;

use App\Enums\MetodePrediksi;
use App\Enums\StatusPrediksi;
use App\Scopes\FacilityScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrediksiObat extends Model
{
    use HasFactory;

    protected $table = 'prediksi_obat';

    protected $fillable = [
        'obat_id',
        'facility_id',
        'periode_bulan',
        'periode_tahun',
        'nilai_prediksi',
        'interval_bawah',
        'interval_atas',
        'confidence_score',
        'metode_model',
        'versi_model',
        'tanggal_prediksi',
        'data_snapshot',
        'faktor_penjelas',
        'status',
        'mape',
        'evaluated_at',
    ];

    protected function casts(): array
    {
        return [
            'metode_model' => MetodePrediksi::class,
            'status' => StatusPrediksi::class,
            'periode_bulan' => 'integer',
            'periode_tahun' => 'integer',
            'nilai_prediksi' => 'decimal:2',
            'interval_bawah' => 'decimal:2',
            'interval_atas' => 'decimal:2',
            'confidence_score' => 'decimal:4',
            'mape' => 'decimal:2',
            'data_snapshot' => 'array',
            'faktor_penjelas' => 'array',
            'tanggal_prediksi' => 'date',
            'evaluated_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new FacilityScope);
    }

    public function obat(): BelongsTo
    {
        return $this->belongsTo(Obat::class);
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }
}
