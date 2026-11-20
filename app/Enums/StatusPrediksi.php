<?php

namespace App\Enums;

enum StatusPrediksi: string
{
    case Draft = 'draft';
    case Divalidasi = 'divalidasi';
    case Dipakai = 'dipakai';
    case Ditolak = 'ditolak';
}
