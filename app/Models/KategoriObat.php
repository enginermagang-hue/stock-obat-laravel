<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KategoriObat extends Model
{
    use HasFactory;

    protected $table = 'kategori_obat';

    protected $fillable = ['kode', 'nama', 'keterangan'];

    public function obat(): HasMany
    {
        return $this->hasMany(Obat::class, 'kategori_id');
    }
}
