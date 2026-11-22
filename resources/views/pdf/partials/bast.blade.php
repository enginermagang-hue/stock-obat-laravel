@php
    $tanggal = $distribution->tanggal_diterima;
    $hari = $tanggal ? $tanggal->locale('id')->translatedFormat('l') : '................';
    $nomorTanggal = $tanggal ? $tanggal->format('j') : '....';
    $bulan = $tanggal ? $tanggal->locale('id')->translatedFormat('F') : '................';
    $tahun = $tanggal ? $tanggal->format('Y') : '....';
    $penerimaUnit = $distribution->penerima_unit ?: $distribution->facility?->nama;
@endphp

<div class="doc-head">
    <h1>BERITA ACARA SERAH TERIMA (BAST)</h1>
    <div class="doc-no">Nomor : {{ $distribution->no_bast ?? '................' }}</div>
</div>

<div class="statement">
    Pada hari ini {{ $hari }} tanggal {{ $nomorTanggal }} bulan {{ $bulan }}, tahun {{ $tahun }}
    saya yang bertanda tangan di bawah ini :
</div>

<div class="identity-group">Yang menyerahkan</div>
<table class="identity">
    <tr>
        <td class="lbl">Nama</td>
        <td class="sep">:</td>
        <td>{{ $distribution->pengirim?->name ?? '................' }}</td>
    </tr>
    <tr>
        <td class="lbl">Jabatan</td>
        <td class="sep">:</td>
        <td>{{ $distribution->pengirim?->jabatan ?: '................' }}</td>
    </tr>
    <tr>
        <td class="lbl">Unit Kerja</td>
        <td class="sep">:</td>
        <td>Instalasi Farmasi Rumah Sakit</td>
    </tr>
</table>

<div class="identity-group">Yang menerima</div>
<table class="identity">
    <tr>
        <td class="lbl">Nama</td>
        <td class="sep">:</td>
        <td>{{ $distribution->penerima_nama ?? '................' }}</td>
    </tr>
    <tr>
        <td class="lbl">NIP</td>
        <td class="sep">:</td>
        <td>{{ $distribution->penerima_nip ?: '................' }}</td>
    </tr>
    <tr>
        <td class="lbl">Jabatan</td>
        <td class="sep">:</td>
        <td>{{ $distribution->penerima_jabatan ?: '................' }}</td>
    </tr>
    <tr>
        <td class="lbl">Unit Kerja</td>
        <td class="sep">:</td>
        <td>{{ $penerimaUnit ?: '................' }}</td>
    </tr>
</table>

<table class="items">
    <thead>
    <tr>
        <th>No</th>
        <th>Nama obat</th>
        <th class="num">Jumlah</th>
        <th>Satuan</th>
    </tr>
    </thead>
    <tbody>
    @foreach ($distribution->items as $index => $item)
        <tr>
            <td>{{ $index + 1 }}</td>
            <td>
                <div>{{ $item->obat?->nama }}</div>
                <div class="item-meta">
                    {{ $item->obat?->bentuk_sediaan?->value ?? '—' }}
                    @if ($item->obat?->kekuatan)
                        / {{ $item->obat->kekuatan }}
                    @endif
                    · Batch {{ $item->batch?->no_batch ?? '—' }}
                    · ED {{ $item->batch?->tanggal_kedaluwarsa?->format('d-m-Y') ?? '—' }}
                </div>
            </td>
            <td class="num">{{ $item->jumlah }}</td>
            <td>{{ $item->obat?->satuan }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<div class="statement">
    Demikian Berita Acara ini dibuat untuk dipergunakan sebagaimana mestinya.
</div>

<table class="sign">
    <tr>
        <td>
            <div>Yang menyerahkan ,</div>
            <div class="line">&nbsp;</div>
        </td>
        <td>
            <div>Yang Menerima</div>
            <div class="sign-name">{{ $distribution->penerima_nama ?? '................' }}</div>
            <div class="sign-nip">NIP. {{ $distribution->penerima_nip ?: '................' }}</div>
        </td>
    </tr>
</table>
