<?php

namespace App\Enums;

enum BentukSediaan: string
{
    case Tablet = 'tablet';
    case Kapsul = 'kapsul';
    case Sirup = 'sirup';
    case Injeksi = 'injeksi';
    case Salep = 'salep';
    case Serbuk = 'serbuk';
    case Tetes = 'tetes';
    case Lainnya = 'lainnya';
}
