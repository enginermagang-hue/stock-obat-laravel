<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rko', function (Blueprint $table) {
            $table->decimal('total', 15, 2)->default(0)->after('catatan');
        });

        DB::table('rko')->update([
            'total' => DB::raw('(select coalesce(sum(total_estimasi), 0) from rko_item where rko_item.rko_id = rko.id)'),
        ]);
    }

    public function down(): void
    {
        Schema::table('rko', function (Blueprint $table) {
            $table->dropColumn('total');
        });
    }
};
