<?php

namespace App\Models;

use App\Enums\JenisSumberDana;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SumberDana extends Model
{
    use HasFactory;

    protected $table = 'sumber_dana';

    protected $fillable = [
        'kode',
        'nama',
        'jenis',
        'kode_rekening',
        'program',
        'keterangan',
        'aktif',
    ];

    protected function casts(): array
    {
        return [
            'jenis' => JenisSumberDana::class,
            'aktif' => 'boolean',
        ];
    }
}
