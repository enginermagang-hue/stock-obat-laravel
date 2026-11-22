<?php

namespace App\Models;

use App\Enums\JenisLicense;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class License extends Model
{
    use HasFactory;

    protected $table = 'licenses';

    protected $fillable = [
        'jenis',
        'nomor',
        'tanggal_terbit',
        'tanggal_kedaluwarsa',
        'file_path',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'jenis' => JenisLicense::class,
            'tanggal_terbit' => 'date',
            'tanggal_kedaluwarsa' => 'date',
        ];
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->whereNotNull('tanggal_kedaluwarsa')
            ->whereDate('tanggal_kedaluwarsa', '<', today());
    }

    public function scopeExpiring(Builder $query, int $days = 30): Builder
    {
        return $query->whereNotNull('tanggal_kedaluwarsa')
            ->whereDate('tanggal_kedaluwarsa', '>=', today())
            ->whereDate('tanggal_kedaluwarsa', '<=', today()->addDays($days));
    }

    public function scopeValid(Builder $query): Builder
    {
        return $query->whereNotNull('tanggal_kedaluwarsa')
            ->whereDate('tanggal_kedaluwarsa', '>=', today());
    }

    public function getStatusAttribute(): string
    {
        if ($this->tanggal_kedaluwarsa === null) {
            return 'tanpa tanggal';
        }

        if ($this->tanggal_kedaluwarsa->isPast()) {
            return 'kedaluwarsa';
        }

        if ($this->tanggal_kedaluwarsa->lessThanOrEqualTo(today()->addDays(30))) {
            return 'segera kedaluwarsa';
        }

        return 'valid';
    }

    public function getSisaHariAttribute(): ?int
    {
        if ($this->tanggal_kedaluwarsa === null) {
            return null;
        }

        return (int) $this->tanggal_kedaluwarsa->diffInDays(today(), false);
    }
}
