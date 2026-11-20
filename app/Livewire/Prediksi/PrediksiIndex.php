<?php

namespace App\Livewire\Prediksi;

use App\Actions\Prediksi\EvaluasiAkurasi;
use App\Actions\Prediksi\PrediksiKebutuhanObat;
use App\Enums\StatusPrediksi;
use App\Models\Facility;
use App\Models\PrediksiObat;
use Livewire\Component;
use Livewire\WithPagination;

class PrediksiIndex extends Component
{
    use WithPagination;

    public int $periode_bulan;
    public int $periode_tahun;
    public ?int $facility_id = null;

    public function mount(): void
    {
        $next = now()->addMonthNoOverflow();
        $this->periode_bulan = (int) $next->month;
        $this->periode_tahun = (int) $next->year;
        $this->facility_id = auth()->user()->facility_id;
    }

    public function run(): void
    {
        abort_unless(auth()->user()->can('manage-prediksi'), 403);

        $data = $this->validate([
            'periode_bulan' => 'required|integer|between:1,12',
            'periode_tahun' => 'required|integer|min:2000',
            'facility_id' => 'nullable|exists:facilities,id',
        ]);

        $facilityId = auth()->user()->facility_id ?: ($data['facility_id'] ?? null);

        $results = app(PrediksiKebutuhanObat::class)->handle(
            $data['periode_bulan'],
            $data['periode_tahun'],
            $facilityId,
            auth()->id(),
        );

        session()->flash('status', $results->count().' prediksi dihitung. Tinjau dan validasi sebelum dipakai di RKO.');
    }

    public function validatePrediction(int $id): void
    {
        abort_unless(auth()->user()->can('manage-prediksi'), 403);
        app(EvaluasiAkurasi::class)->handle(PrediksiObat::findOrFail($id));
        session()->flash('status', 'Prediksi divalidasi dan siap dipakai di RKO.');
    }

    public function render()
    {
        $predictions = PrediksiObat::with(['obat', 'facility'])
            ->orderByDesc('periode_tahun')
            ->orderByDesc('periode_bulan')
            ->orderBy('obat_id')
            ->paginate(15);

        $summary = PrediksiObat::query()
            ->where('periode_tahun', $this->periode_tahun)
            ->where('periode_bulan', $this->periode_bulan)
            ->selectRaw('count(*) as total, avg(mape) as avg_mape, sum(case when confidence_score < 0.6 then 1 else 0 end) as low_confidence')
            ->first();

        return view('livewire.prediksi.prediksi-index', [
            'predictions' => $predictions,
            'facilities' => Facility::where('jenis', 'puskesmas')->orderBy('nama')->get(),
            'summary' => $summary,
        ])->layout('layouts.app', ['title' => 'Prediksi Kebutuhan']);
    }
}
