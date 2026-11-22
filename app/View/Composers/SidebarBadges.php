<?php

namespace App\View\Composers;

use App\Enums\StatusLplpo;
use App\Enums\StatusPermintaan;
use App\Enums\StatusPurchaseOrder;
use App\Models\BatchObat;
use App\Models\Lplpo;
use App\Models\Obat;
use App\Models\PermintaanObat;
use App\Models\PurchaseOrder;
use Illuminate\View\View;

class SidebarBadges
{
    /**
     * Bagikan jumlah tunggakan operasional per grup menu sidebar.
     */
    public function compose(View $view): void
    {
        $user = auth()->user();

        if ($user === null) {
            $view->with('sidebarBadges', []);

            return;
        }

        $badges = [];

        if ($user->can('manage-stock')) {
            $stokMenipis = Obat::query()
                ->whereRaw('(select coalesce(sum(jumlah_sisa), 0) from batch_obat where batch_obat.obat_id = obat.id) <= obat.stok_minimum')
                ->count();

            $badges['Stok'] = $stokMenipis + BatchObat::expiring(90)->count();
        }

        if ($user->can('verify-lplpo') || $user->can('verify-permintaan')) {
            $badges['Perencanaan'] = Lplpo::where('status', StatusLplpo::Dikirim)->count()
                + PermintaanObat::whereIn('status', [StatusPermintaan::Dikirim, StatusPermintaan::Diterima])->count();
        }

        if ($user->can('receive-goods')) {
            $badges['Pembelian'] = PurchaseOrder::where('status', StatusPurchaseOrder::Dikirim)->count();
        }

        $view->with('sidebarBadges', array_filter($badges, fn (int $count): bool => $count > 0));
    }
}
