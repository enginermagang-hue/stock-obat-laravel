<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rko', function (Blueprint $table) {
            $table->id();
            $table->string('no_rko')->unique();
            $table->unsignedSmallInteger('tahun');
            $table->unsignedTinyInteger('periode');
            $table->string('tingkat')->default('rs');
            $table->string('metode')->default('historis');
            $table->unsignedTinyInteger('lead_time_bulan')->default(1);
            $table->decimal('faktor_pengaman', 5, 2)->default(1.00);
            $table->decimal('bobot_ai', 4, 3)->nullable();
            $table->foreignId('sumber_dana_id')->nullable()->constrained('sumber_dana')->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('supplier')->nullOnDelete();
            $table->string('status')->default('draft');
            $table->unsignedInteger('versi')->default(1);
            $table->foreignId('disusun_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('disetujui_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->index(['tahun', 'periode', 'tingkat']);
        });

        Schema::create('rko_item', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rko_id')->constrained('rko')->cascadeOnDelete();
            $table->foreignId('obat_id')->constrained('obat')->restrictOnDelete();
            $table->foreignId('sumber_dana_id')->nullable()->constrained('sumber_dana')->nullOnDelete();
            $table->decimal('pemakaian_rata_rata', 15, 2)->default(0);
            $table->decimal('estimasi_pemakaian', 15, 2)->default(0);
            $table->unsignedInteger('stok_akhir')->default(0);
            $table->unsignedInteger('stok_dalam_pesanan')->default(0);
            $table->decimal('stok_pengaman', 15, 2)->default(0);
            $table->decimal('kebutuhan_bersih', 15, 2)->default(0);
            $table->decimal('harga_estimasi', 15, 2)->default(0);
            $table->decimal('total_estimasi', 15, 2)->default(0);
            $table->string('sumber')->nullable();
            $table->text('catatan')->nullable();
            $table->unsignedBigInteger('prediksi_id')->nullable();
            $table->decimal('prediksi_ai', 15, 2)->nullable();
            $table->decimal('prediksi_interval_bawah', 15, 2)->nullable();
            $table->decimal('prediksi_interval_atas', 15, 2)->nullable();
            $table->decimal('prediksi_confidence', 5, 4)->nullable();
            $table->text('alasan_pilihan')->nullable();
            $table->timestamps();

            $table->unique(['rko_id', 'obat_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rko_item');
        Schema::dropIfExists('rko');
    }
};
