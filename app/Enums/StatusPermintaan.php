<?php

namespace App\Enums;

enum StatusPermintaan: string
{
    case Draft = 'draft';
    case Dikirim = 'dikirim';
    case Diterima = 'diterima';
    case Diverifikasi = 'diverifikasi';
    case Disetujui = 'disetujui';
    case Sebagian = 'sebagian';
    case Ditolak = 'ditolak';
    case Diproses = 'diproses';
    case Selesai = 'selesai';
    case Dibatalkan = 'dibatalkan';
}
