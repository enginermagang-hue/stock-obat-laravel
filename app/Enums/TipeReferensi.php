<?php

namespace App\Enums;

enum TipeReferensi: string
{
    case Penerimaan = 'penerimaan';
    case Distribusi = 'distribusi';
    case Penyesuaian = 'penyesuaian';
    case Opname = 'opname';
}
