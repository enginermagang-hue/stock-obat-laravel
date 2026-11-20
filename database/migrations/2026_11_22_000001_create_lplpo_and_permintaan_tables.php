<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lplpo', function (Blueprint $table) {
            $table->id();
            $table->string('no_lplpo')->unique();
            $table->foreignId('facility_id')->constrained('facilities')->restrictOnDelete();
            $table->unsignedTinyInteger('bulan');
            $table->unsignedSmallInteger('tahun');
            $table->date('tanggal_penyerahan')->nullable();
            $table->string('status')->default('draft');
            $table->unsignedInteger('versi')->default(1);
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->unique(['facility_id', 'bulan', 'tahun', 'versi']);
            $table->index(['tahun', 'bulan']);
        });

        Schema::create('lplpo_item', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lplpo_id')->constrained('lplpo')->cascadeOnDelete();
            $table->foreignId('obat_id')->constrained('obat')->restrictOnDelete();
            $table->unsignedInteger('stok_awal')->default(0);
            $table->unsignedInteger('pemasukan')->default(0);
            $table->unsignedInteger('pemakaian')->default(0);
            $table->unsignedInteger('stok_akhir')->default(0);
            $table->unsignedInteger('stok_akhir_sistem')->default(0);
            $table->unsignedInteger('stok_minimum')->default(0);
            $table->unsignedInteger('jumlah_diminta')->default(0);
            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->unique(['lplpo_id', 'obat_id']);
        });

        Schema::create('permintaan_obat', function (Blueprint $table) {
            $table->id();
            $table->string('no_permintaan')->unique();
            $table->foreignId('facility_id')->constrained('facilities')->restrictOnDelete();
            $table->foreignId('lplpo_id')->nullable()->constrained('lplpo')->nullOnDelete();
            $table->string('sumber');
            $table->date('periode_awal')->nullable();
            $table->date('periode_akhir')->nullable();
            $table->date('tanggal');
            $table->string('status')->default('draft');
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('catatan_verifikasi')->nullable();
            $table->timestamps();
        });

        Schema::create('permintaan_obat_item', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permintaan_id')->constrained('permintaan_obat')->cascadeOnDelete();
            $table->foreignId('obat_id')->constrained('obat')->restrictOnDelete();
            $table->unsignedInteger('jumlah_diminta');
            $table->unsignedInteger('jumlah_disetujui')->nullable();
            $table->string('status_item')->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->unique(['permintaan_id', 'obat_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permintaan_obat_item');
        Schema::dropIfExists('permintaan_obat');
        Schema::dropIfExists('lplpo_item');
        Schema::dropIfExists('lplpo');
    }
};
