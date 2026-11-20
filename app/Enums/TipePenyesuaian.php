<?php

namespace App\Enums;

enum TipePenyesuaian: string
{
    case Koreksi = 'koreksi';
    case Expired = 'expired';
    case Rusak = 'rusak';
    case Hilang = 'hilang';
    case Retur = 'retur';
    case Opname = 'opname';
}
