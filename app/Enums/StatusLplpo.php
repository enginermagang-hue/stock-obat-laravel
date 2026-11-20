<?php

namespace App\Enums;

enum StatusLplpo: string
{
    case Draft = 'draft';
    case Dikirim = 'dikirim';
    case Diverifikasi = 'diverifikasi';
    case Ditolak = 'ditolak';
}
