<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('distribution', function (Blueprint $table) {
            $table->string('no_sbbk')->nullable()->unique()->after('no_surat');
            $table->date('tanggal_keluar')->nullable()->after('tanggal');
            $table->string('no_bast')->nullable()->unique()->after('penerima_nama');
        });
    }

    public function down(): void
    {
        Schema::table('distribution', function (Blueprint $table) {
            $table->dropUnique(['no_sbbk']);
            $table->dropUnique(['no_bast']);
            $table->dropColumn(['no_sbbk', 'tanggal_keluar', 'no_bast']);
        });
    }
};
