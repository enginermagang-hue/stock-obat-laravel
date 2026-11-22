<?php

namespace App\Enums;

enum JenisLicense: string
{
    case IzinApotek = 'izin_apotek';
    case IzinEdar = 'izin_edar';
    case Cdob = 'cdob';
    case Lainnya = 'lainnya';

    public function label(): string
    {
        return match ($this) {
            self::IzinApotek => 'Izin apotek',
            self::IzinEdar => 'Izin edar',
            self::Cdob => 'CDOB',
            self::Lainnya => 'Lainnya',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
