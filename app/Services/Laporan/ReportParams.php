<?php

namespace App\Services\Laporan;

use Illuminate\Http\Request;

class ReportParams
{
    /** @return array<string, mixed> */
    public static function fromRequest(Request $request): array
    {
        return [
            'tahun' => (int) $request->input('tahun', now()->year),
            'obat_id' => $request->filled('obat_id') ? $request->integer('obat_id') : null,
            'facility_id' => $request->user()?->facility_id
                ?? ($request->filled('facility_id') ? $request->integer('facility_id') : null),
            'from' => $request->input('from') ?: null,
            'to' => $request->input('to') ?: null,
            'days' => (int) $request->input('days', 90),
            'event' => $request->input('event') ?: null,
            'search' => $request->input('search') ?: null,
        ];
    }
}
