<?php

namespace App\Enums;

enum StatusPurchaseOrder: string
{
    case Draft = 'draft';
    case Dikirim = 'dikirim';
    case DiterimaSebagian = 'diterima_sebagian';
    case Diterima = 'diterima';
    case Dibatalkan = 'dibatalkan';
}
