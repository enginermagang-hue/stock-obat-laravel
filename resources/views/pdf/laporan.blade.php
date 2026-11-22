<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan {{ $report['title'] }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1f2937; }
        h1 { font-size: 15px; margin: 0 0 4px; }
        .muted { color: #6b7280; font-size: 9px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #d1d5db; padding: 4px 5px; vertical-align: top; }
        th { background: #f3f4f6; text-align: left; }
        .num { text-align: right; }
    </style>
</head>
<body>
<h1>{{ $report['title'] }}</h1>
<div class="muted">
    Dibuat {{ $generatedAt->format('d-m-Y H:i') }}
    · Tahun {{ $params['tahun'] ?? '-' }}
    · {{ count($report['rows']) }} baris
</div>

<table>
    <thead>
    <tr>
        @foreach ($report['headings'] as $label)
            <th>{{ $label }}</th>
        @endforeach
    </tr>
    </thead>
    <tbody>
    @forelse ($report['rows'] as $row)
        <tr>
            @foreach (array_keys($report['headings']) as $key)
                @php $value = $row[$key] ?? null; @endphp
                <td class="{{ is_numeric($value) ? 'num' : '' }}">
                    {{ $value === null || $value === '' ? '—' : (is_numeric($value) ? number_format((float) $value, 0, ',', '.') : $value) }}
                </td>
            @endforeach
        </tr>
    @empty
        <tr><td colspan="{{ count($report['headings']) }}">Tidak ada data.</td></tr>
    @endforelse
    </tbody>
</table>
</body>
</html>
