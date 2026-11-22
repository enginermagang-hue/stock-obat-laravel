<div class="header">
    <h1>SURAT JALAN</h1>
    <div class="muted">No. {{ $distribution->no_surat }} · {{ $distribution->tanggal?->format('d-m-Y') }}</div>
</div>

<table class="grid">
    <tr>
        <td style="width: 50%">
            <strong>Kepada</strong><br>
            {{ $distribution->facility?->nama }}<br>
            {{ $distribution->facility?->kode }}<br>
            {{ $distribution->facility?->alamat }}
        </td>
        <td style="width: 50%">
            <strong>Referensi</strong><br>
            <span class="label">Permintaan:</span> {{ $distribution->permintaan?->no_permintaan ?? '—' }}<br>
            <span class="label">SBBK:</span> {{ $distribution->no_sbbk ?? '—' }}<br>
            <span class="label">Tanggal kirim:</span> {{ $distribution->tanggal_keluar?->format('d-m-Y') ?? '—' }}<br>
            <span class="label">Status:</span> {{ $distribution->status->value }}
        </td>
    </tr>
</table>

<table class="items">
    <thead>
    <tr>
        <th>No</th>
        <th>Nama obat</th>
        <th>Bentuk / kekuatan</th>
        <th>Batch</th>
        <th>Expiry</th>
        <th class="num">Jumlah</th>
    </tr>
    </thead>
    <tbody>
    @foreach ($distribution->items as $index => $item)
        <tr>
            <td>{{ $index + 1 }}</td>
            <td>{{ $item->obat?->nama }}</td>
            <td>
                {{ $item->obat?->bentuk_sediaan?->value ?? '—' }}
                @if ($item->obat?->kekuatan)
                    / {{ $item->obat->kekuatan }}
                @endif
            </td>
            <td>{{ $item->batch?->no_batch }}</td>
            <td>{{ $item->batch?->tanggal_kedaluwarsa?->format('d-m-Y') }}</td>
            <td class="num">{{ $item->jumlah }} {{ $item->obat?->satuan }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

@if ($distribution->catatan)
    <p class="muted">Catatan: {{ $distribution->catatan }}</p>
@endif

<table class="sign">
    <tr>
        <td>
            <div class="role">Pengirim</div>
            <div class="muted">Instalasi Farmasi Rumah Sakit</div>
            <div class="line">{{ $distribution->pengirim?->name ?? '................' }}</div>
            <div class="muted">Tanggal: {{ $distribution->tanggal_keluar?->format('d-m-Y') ?? '................' }}</div>
        </td>
        <td>
            <div class="role">Penerima</div>
            <div class="muted">{{ $distribution->facility?->nama }}</div>
            <div class="line">{{ $distribution->penerima_nama ?? '................' }}</div>
            <div class="muted">Tanggal: {{ $distribution->tanggal_diterima?->format('d-m-Y') ?? '................' }}</div>
        </td>
    </tr>
</table>

<div class="note">
    Surat jalan ini menyertai pengiriman barang. Barang agar diperiksa sesuai jenis, jumlah, nomor batch,
    dan tanggal kedaluwarsa sebelum ditandatangani.
</div>
