<?php

namespace App\Actions\Rko;

use App\Enums\MetodeRko;
use App\Enums\StatusRko;
use App\Enums\TingkatRko;
use App\Models\BatchObat;
use App\Models\Obat;
use App\Models\Rko;
use Illuminate\Support\Facades\DB;

class BuildRko
{
    public function __construct(private readonly HitungPemakaianRataRata $average) {}

    /**
     * @param  array{tingkat?:string,lead_time_bulan?:int,faktor_pengaman?:float,sumber_dana_id?:?int,supplier_id?:?int,catatan?:?string}  $options
     */
    public function handle(int $tahun, int $periode, array $options = [], ?int $userId = null): Rko
    {
        return DB::transaction(function () use ($tahun, $periode, $options, $userId) {
            $versi = (int) Rko::where('tahun', $tahun)
                ->where('periode', $periode)
                ->where('tingkat', $options['tingkat'] ?? TingkatRko::Rs->value)
                ->max('versi') + 1;

            $rko = Rko::create([
                'no_rko' => $this->nextNumber($tahun),
                'tahun' => $tahun,
                'periode' => $periode,
                'tingkat' => $options['tingkat'] ?? TingkatRko::Rs->value,
                'metode' => MetodeRko::Historis,
                'lead_time_bulan' => (int) ($options['lead_time_bulan'] ?? 1),
                'faktor_pengaman' => (float) ($options['faktor_pengaman'] ?? 1),
                'sumber_dana_id' => $options['sumber_dana_id'] ?? null,
                'supplier_id' => $options['supplier_id'] ?? null,
                'status' => StatusRko::Draft,
                'versi' => $versi,
                'disusun_oleh' => $userId ?? auth()->id(),
                'catatan' => $options['catatan'] ?? null,
            ]);

            $total = 0;
            $leadTime = (int) $rko->lead_time_bulan;
            $faktor = (float) $rko->faktor_pengaman;

            Obat::where('aktif', true)->orderBy('nama')->each(function (Obat $obat) use ($rko, $leadTime, $faktor, &$total) {
                $rataRata = $this->average->handle($obat->id);
                $estimasi = $rataRata;
                $stokAkhir = (int) BatchObat::where('obat_id', $obat->id)->sum('jumlah_sisa');
                $dalamPesanan = (int) DB::table('purchase_order_item')
                    ->join('purchase_order', 'purchase_order.id', '=', 'purchase_order_item.po_id')
                    ->where('purchase_order_item.obat_id', $obat->id)
                    ->whereIn('purchase_order.status', ['draft', 'dikirim', 'diterima_sebagian'])
                    ->sum(DB::raw('purchase_order_item.jumlah - purchase_order_item.jumlah_diterima'));
                $pengaman = $rataRata * $leadTime * $faktor;
                $kebutuhan = max(0, $estimasi + $pengaman - $stokAkhir - $dalamPesanan);
                $harga = (float) $obat->harga_satuan_terakhir;
                $subtotal = $kebutuhan * $harga;

                $rko->items()->create([
                    'obat_id' => $obat->id,
                    'sumber_dana_id' => $rko->sumber_dana_id,
                    'pemakaian_rata_rata' => $rataRata,
                    'estimasi_pemakaian' => $estimasi,
                    'stok_akhir' => $stokAkhir,
                    'stok_dalam_pesanan' => $dalamPesanan,
                    'stok_pengaman' => $pengaman,
                    'kebutuhan_bersih' => $kebutuhan,
                    'harga_estimasi' => $harga,
                    'total_estimasi' => $subtotal,
                    'sumber' => 'historis',
                ]);

                $total += $subtotal;
            });

            $rko->update(['total' => $total]);

            return $rko->load('items.obat');
        });
    }

    private function nextNumber(int $tahun): string
    {
        $prefix = 'RKO-'.$tahun.'-';
        $last = Rko::where('no_rko', 'like', $prefix.'%')->orderByDesc('id')->value('no_rko');
        $sequence = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
