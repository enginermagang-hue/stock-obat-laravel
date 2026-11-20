<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prediksi_obat', function (Blueprint $table) {
            $table->id();
            $table->foreignId('obat_id')->constrained('obat')->cascadeOnDelete();
            $table->foreignId('facility_id')->nullable()->constrained('facilities')->nullOnDelete();
            $table->unsignedTinyInteger('periode_bulan');
            $table->unsignedSmallInteger('periode_tahun');
            $table->decimal('nilai_prediksi', 15, 2);
            $table->decimal('interval_bawah', 15, 2)->nullable();
            $table->decimal('interval_atas', 15, 2)->nullable();
            $table->decimal('confidence_score', 5, 4)->nullable();
            $table->string('metode_model');
            $table->string('versi_model');
            $table->date('tanggal_prediksi');
            $table->json('data_snapshot')->nullable();
            $table->json('faktor_penjelas')->nullable();
            $table->string('status')->default('draft');
            $table->decimal('mape', 6, 2)->nullable();
            $table->timestamp('evaluated_at')->nullable();
            $table->timestamps();

            $table->index(['obat_id', 'facility_id', 'periode_tahun', 'periode_bulan']);
            $table->unique(
                ['obat_id', 'facility_id', 'periode_bulan', 'periode_tahun', 'metode_model', 'versi_model'],
                'prediksi_obat_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prediksi_obat');
    }
};
