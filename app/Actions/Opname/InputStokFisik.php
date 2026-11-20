<?php

namespace App\Actions\Opname;

use App\Enums\ModeOpname;
use App\Enums\StatusOpname;
use App\Models\StockOpname;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InputStokFisik
{
    /**
     * @param  array<int, int|null>  $fisik  keyed by stock_opname_item id
     */
    public function handle(StockOpname $opname, array $fisik): StockOpname
    {
        return DB::transaction(function () use ($opname, $fisik) {
            $locked = StockOpname::query()->whereKey($opname->getKey())->lockForUpdate()->firstOrFail();

            if (! in_array($locked->status, [StatusOpname::Draft, StatusOpname::Proses], true)) {
                throw ValidationException::withMessages(['status' => 'Opname tidak dapat diinput pada status ini.']);
            }

            foreach ($locked->items as $item) {
                if (! array_key_exists($item->id, $fisik)) {
                    continue;
                }

                $value = $fisik[$item->id];
                $item->update([
                    'stok_fisik' => $value,
                    'selisih' => $value === null ? null : ((int) $value - $item->stok_sistem),
                ]);
            }

            if ($locked->mode === ModeOpname::Buta && $locked->items()->whereNull('stok_fisik')->exists()) {
                throw ValidationException::withMessages([
                    'fisik' => 'Mode buta: seluruh stok fisik harus terisi sebelum disimpan.',
                ]);
            }

            $locked->update(['status' => StatusOpname::Proses]);

            return $locked->refresh()->load('items');
        });
    }
}
