<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Dokumen Distribusi {{ $distribution->no_surat }}</title>
    <style>
        @page { margin: 26px 28px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; }
        h1 { font-size: 16px; margin: 0 0 2px; text-transform: uppercase; }
        .muted { color: #6b7280; }
        .header { border-bottom: 2px solid #1f2937; padding-bottom: 10px; margin-bottom: 14px; }
        .grid { width: 100%; margin-bottom: 14px; }
        .grid td { vertical-align: top; padding: 2px 0; }
        .grid .label { color: #6b7280; }
        table.items { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        table.items th, table.items td { border: 1px solid #d1d5db; padding: 5px 6px; }
        table.items th { background: #f3f4f6; text-align: left; }
        .num { text-align: right; }
        .total { text-align: right; font-weight: bold; margin-bottom: 10px; }
        .statement { margin: 10px 0 14px; line-height: 1.6; }
        .sign { width: 100%; margin-top: 26px; }
        .sign td { width: 50%; text-align: center; padding-top: 6px; vertical-align: top; }
        .sign .role { font-weight: bold; }
        .sign .line { border-top: 1px solid #1f2937; padding-top: 4px; margin-top: 50px; }
        .note { margin-top: 18px; font-size: 9px; color: #6b7280; }
        .doc-title { margin-top: 4px; }
        .doc-head { text-align: center; margin-bottom: 14px; }
        .doc-head h1 { font-size: 15px; margin: 0 0 2px; text-decoration: underline; }
        .doc-no { font-size: 11px; }
        table.identity { width: 100%; border-collapse: collapse; margin: 4px 0 10px; }
        table.identity td { vertical-align: top; padding: 1px 0; }
        table.identity .lbl { width: 92px; }
        table.identity .sep { width: 10px; }
        .identity-group { font-weight: bold; margin-top: 8px; }
        .item-meta { font-size: 9px; line-height: 1.4; color: #6b7280; }
        .sign-name { margin-top: 50px; font-weight: bold; text-decoration: underline; }
        .sign-nip { margin-top: 2px; }
        table.kop { width: 100%; border-collapse: collapse; }
        table.kop td { vertical-align: middle; padding: 0; }
        .kop-logo { width: 68px; text-align: center; }
        .kop-logo img { width: 58px; height: 58px; }
        .kop-text { text-align: center; }
        .kop-line1 { font-size: 14px; font-weight: bold; letter-spacing: 0.3px; text-transform: uppercase; }
        .kop-line2 { font-size: 13px; font-weight: bold; text-transform: uppercase; }
        .kop-line3 { font-size: 10px; color: #374151; }
        .kop-rule { border-top: 2.5px solid #1f2937; border-bottom: 1px solid #1f2937; height: 2px; margin: 6px 0 14px; }
    </style>
</head>
<body>
@php
    $dokumen = $dokumen ?? 'semua';
    $docs = $dokumen === 'semua' ? ['surat-jalan', 'sbbk', 'bast'] : [$dokumen];
@endphp

@foreach ($docs as $doc)
    @include('pdf.partials.kop', ['distribution' => $distribution])
    @include('pdf.partials.'.$doc, ['distribution' => $distribution])

    @unless ($loop->last)
        <div style="page-break-after: always;"></div>
    @endunless
@endforeach
</body>
</html>
