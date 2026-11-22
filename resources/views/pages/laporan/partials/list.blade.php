@php
    $exportParams = array_filter($params, fn ($value) => $value !== null && $value !== '');
@endphp

<div class="os-section os-toolbar">
    <a href="{{ route('laporan.export', ['report' => $data['key'], 'format' => 'xlsx'] + $exportParams) }}"
       class="os-btn os-btn--secondary">
        <x-icon name="file-excel-line" /> Excel
    </a>
    <a href="{{ route('laporan.export', ['report' => $data['key'], 'format' => 'pdf'] + $exportParams) }}"
       class="os-btn os-btn--secondary">
        <x-icon name="file-pdf-line" /> PDF
    </a>
</div>

<div class="os-section os-card os-card--flush">
    <div class="os-card-head">
        <div>
            <div class="os-card-title">{{ $data['title'] }}</div>
            <div class="os-card-subtitle">{{ count($data['rows']) }} baris</div>
        </div>
    </div>
    <div class="os-table-wrap">
        <table class="os-table os-table--hover">
            <thead>
            <tr>
                @foreach ($data['headings'] as $label)
                    <th>{{ $label }}</th>
                @endforeach
            </tr>
            </thead>
            <tbody>
            @forelse ($data['rows'] as $row)
                <tr>
                    @foreach (array_keys($data['headings']) as $key)
                        @php $value = $row[$key] ?? null; @endphp
                        <td>
                            @if ($value === null || $value === '')
                                —
                            @elseif (is_numeric($value) && preg_match('/pagu|komitmen|realisasi|sisa|nilai|total|prediksi|aktual|selisih/i', $key))
                                {{ number_format((float) $value, 0, ',', '.') }}
                            @else
                                {{ $value }}
                            @endif
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($data['headings']) }}" class="os-empty">
                        Tidak ada data untuk filter ini.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
