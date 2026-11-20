<?php

namespace App\Enums;

enum StatusOpname: string
{
    case Draft = 'draft';
    case Proses = 'proses';
    case Diverifikasi = 'diverifikasi';
    case Disetujui = 'disetujui';
    case Selesai = 'selesai';
    case Dibatalkan = 'dibatalkan';
}
