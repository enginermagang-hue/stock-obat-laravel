<?php

namespace App\Enums;

enum StatusPenyesuaian: string
{
    case Draft = 'draft';
    case Disetujui = 'disetujui';
    case Diproses = 'diproses';
    case Dibatalkan = 'dibatalkan';
}
