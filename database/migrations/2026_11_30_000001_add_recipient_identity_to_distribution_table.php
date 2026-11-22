<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('distribution', function (Blueprint $table) {
            $table->string('penerima_nip', 30)->nullable()->after('penerima_nama');
            $table->string('penerima_jabatan', 120)->nullable()->after('penerima_nip');
            $table->string('penerima_unit', 160)->nullable()->after('penerima_jabatan');
        });
    }

    public function down(): void
    {
        Schema::table('distribution', function (Blueprint $table) {
            $table->dropColumn(['penerima_nip', 'penerima_jabatan', 'penerima_unit']);
        });
    }
};
