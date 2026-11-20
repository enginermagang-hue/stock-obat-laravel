<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('obat', function (Blueprint $table) {
            $table->id();
            $table->string('kode_obat')->unique();
            $table->string('nama');
            $table->string('nama_generik')->nullable();
            $table->foreignId('kategori_id')->nullable()->constrained('kategori_obat')->nullOnDelete();
            $table->string('bentuk_sediaan');
            $table->string('kekuatan')->nullable();
            $table->string('satuan');
            $table->string('golongan')->default('bebas');
            $table->string('no_izin_edar')->nullable();
            $table->string('produsen')->nullable();
            $table->decimal('harga_satuan_terakhir', 15, 2)->default(0);
            $table->unsignedInteger('stok_minimum')->default(0);
            $table->boolean('butuh_cold_chain')->default(false);
            $table->boolean('aktif')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('obat');
    }
};
