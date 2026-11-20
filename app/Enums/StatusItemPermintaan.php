<?php

namespace App\Enums;

enum StatusItemPermintaan: string
{
    case Disetujui = 'disetujui';
    case Sebagian = 'sebagian';
    case Backorder = 'backorder';
    case Ditolak = 'ditolak';
    case Selesai = 'selesai';
}
