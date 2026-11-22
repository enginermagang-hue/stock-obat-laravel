<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('distribution', function (Blueprint $table) {
            $table->id();
            $table->string('no_surat')->unique();
            $table->foreignId('facility_id')->constrained('facilities')->restrictOnDelete();
            $table->foreignId('permintaan_id')->nullable()->constrained('permintaan_obat')->nullOnDelete();
            $table->date('tanggal');
            $table->string('status')->default('draft');
            $table->foreignId('pengirim_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('penerima_nama')->nullable();
            $table->date('tanggal_diterima')->nullable();
            $table->string('file_tanda_terima')->nullable();
            $table->text('catatan')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['facility_id', 'status']);
        });

        Schema::create('distribution_item', function (Blueprint $table) {
            $table->id();
            $table->foreignId('distribution_id')->constrained('distribution')->cascadeOnDelete();
            $table->foreignId('permintaan_item_id')->nullable()->constrained('permintaan_obat_item')->nullOnDelete();
            $table->foreignId('obat_id')->constrained('obat')->restrictOnDelete();
            $table->foreignId('batch_id')->constrained('batch_obat')->restrictOnDelete();
            $table->unsignedInteger('jumlah');
            $table->decimal('harga_satuan', 15, 2);
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->index(['obat_id', 'batch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('distribution_item');
        Schema::dropIfExists('distribution');
    }
};
