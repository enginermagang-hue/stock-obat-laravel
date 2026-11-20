<div>
    <h1 class="text-xl font-semibold">Dashboard</h1>
    <p class="mt-1 text-sm text-slate-500">Ringkasan data fondasi. Kartu stok dan anggaran tersedia setelah Fase 2 dan Fase 6.</p>

    <div class="mt-5 grid gap-4 sm:grid-cols-3">
        <div class="rounded-lg border border-slate-200 bg-white p-5">
            <div class="text-2xl font-bold">{{ $totalObat }}</div>
            <div class="mt-1 text-xs text-slate-500">Jenis obat aktif</div>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-5">
            <div class="text-2xl font-bold">{{ $totalPuskesmas }}</div>
            <div class="mt-1 text-xs text-slate-500">Puskesmas terdaftar</div>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-5">
            <div class="text-2xl font-bold">{{ $totalSumberDana }}</div>
            <div class="mt-1 text-xs text-slate-500">Sumber dana</div>
        </div>
    </div>

    <div class="mt-5 grid gap-4 md:grid-cols-2">
        <div class="rounded-lg border border-slate-200 bg-white p-5">
            <h2 class="text-sm font-semibold">Tugas menunggu</h2>
            <p class="mt-2 text-xs text-slate-500">Modul LPLPO, permintaan, dan opname masuk pada Fase 2–3.</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-5">
            <h2 class="text-sm font-semibold">Tren pemakaian obat</h2>
            <div class="mt-3 grid h-32 place-items-center rounded border border-dashed border-slate-300 text-xs text-slate-400">
                [ Grafik pemakaian — Fase 3 ]
            </div>
        </div>
    </div>
</div>
