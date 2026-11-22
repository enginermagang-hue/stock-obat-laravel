<div class="header">
    <h1>SURAT BUKTI BARANG KELUAR (SBBK)</h1>
    <div class="muted">
        No. {{ $distribution->no_sbbk ?? '—' }}
        · {{ $distribution->tanggal_keluar?->format('d-m-Y') ?? '—' }}
    </div>
</div>

<table class="grid">
    <tr>
        <td style="width: 50%">
            <strong>Asal barang</strong><br>
            Instalasi Farmasi Rumah Sakit<br>
            <span class="label">Diserahkan oleh:</span> {{ $distribution->pengirim?->name ?? '................' }}
        </td>
        <td style="width: 50%">
            <strong>Tujuan</strong><br>
            {{ $distribution->facility?->nama }}<br>
            <span class="label">Surat jalan:</span> {{ $distribution->no_surat }}<br>
            <span class="label">Permintaan:</span> {{ $distribution->permintaan?->no_permintaan ?? '—' }}
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
        <th class="num">Jumlah keluar</th>
        <th class="num">Harga perolehan</th>
        <th class="num">Subtotal</th>
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
            <td class="num">Rp {{ number_format((float) $item->harga_satuan, 0, ',', '.') }}</td>
            <td class="num">Rp {{ number_format((float) $item->subtotal, 0, ',', '.') }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<div class="total">Total nilai keluar: Rp {{ number_format((float) $distribution->items->sum('subtotal'), 0, ',', '.') }}</div>

<table class="sign">
    <tr>
        <td>
            <div class="role">Apoteker Penanggung Jawab (Pengirim)</div>
            <div class="muted">Instalasi Farmasi Rumah Sakit</div>
            <div class="line">{{ $distribution->pengirim?->name ?? '................' }}</div>
            <div class="muted">No. SIPA/STR: ................................</div>
            <div class="muted">Stempel</div>
        </td>
        <td>
            <div class="role">Petugas Gudang</div>
            <div class="muted">Yang menyerahkan barang</div>
            <div class="line">................</div>
            <div class="muted">Tanggal: {{ $distribution->tanggal_keluar?->format('d-m-Y') ?? '................' }}</div>
        </td>
    </tr>
</table>

<div class="note">
    SBBK ini merupakan bukti pengurangan stok/barang keluar dari gudang farmasi dan menjadi dasar pencatatan mutasi stok.
    Simpan sebagai arsip minimal 3 tahun.
</div>
