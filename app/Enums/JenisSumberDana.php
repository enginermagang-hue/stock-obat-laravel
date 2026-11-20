<?php

namespace App\Enums;

enum JenisSumberDana: string
{
    case Apbn = 'apbn';
    case ApbdProv = 'apbd_prov';
    case ApbdKab = 'apbd_kab';
    case Bok = 'bok';
    case Dak = 'dak';
    case Bpjs = 'bpjs';
    case Hibah = 'hibah';
    case Swadaya = 'swadaya';
    case Retribusi = 'retribusi';
    case Lainnya = 'lainnya';
}
