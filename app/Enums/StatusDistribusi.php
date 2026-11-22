<?php

namespace App\Enums;

enum StatusDistribusi: string
{
    case Draft = 'draft';
    case Disetujui = 'disetujui';
    case Dikirim = 'dikirim';
    case Diterima = 'diterima';
    case Dibatalkan = 'dibatalkan';
}
