<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('batch_obat', function (Blueprint $table) {
            $table->id();
            $table->foreignId('obat_id')->constrained('obat')->restrictOnDelete();
            $table->string('no_batch');
            $table->date('tanggal_produksi')->nullable();
            $table->date('tanggal_kedaluwarsa');
            $table->unsignedInteger('jumlah_awal')->default(0);
            $table->unsignedInteger('jumlah_sisa')->default(0);
            $table->decimal('harga_beli', 15, 2)->default(0);
            $table->string('lokasi_rak')->nullable();
            $table->foreignId('sumber_dana_id')->nullable()->constrained('sumber_dana')->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('supplier')->nullOnDelete();
            $table->boolean('aktif')->default(true);
            $table->timestamps();

            $table->unique(['obat_id', 'no_batch']);
            $table->index('tanggal_kedaluwarsa');
        });

        Schema::create('stock_ledger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('batch_obat')->restrictOnDelete();
            $table->string('tipe');
            $table->unsignedInteger('jumlah');
            $table->unsignedInteger('jumlah_sebelum');
            $table->unsignedInteger('jumlah_sesudah');
            $table->string('tipe_referensi');
            $table->unsignedBigInteger('referensi_id');
            $table->dateTime('tanggal_transaksi');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('catatan')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['tipe_referensi', 'referensi_id']);
            $table->index('tanggal_transaksi');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_ledger');
        Schema::dropIfExists('batch_obat');
    }
};
