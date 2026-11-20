<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_opname', function (Blueprint $table) {
            $table->id();
            $table->string('no_opname')->unique();
            $table->date('tanggal');
            $table->unsignedTinyInteger('periode_bulan');
            $table->unsignedSmallInteger('periode_tahun');
            $table->string('tipe');
            $table->string('mode')->default('buta');
            $table->foreignId('facility_id')->nullable()->constrained('facilities')->nullOnDelete();
            $table->string('status')->default('draft');
            $table->foreignId('petugas_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('disetujui_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->text('catatan')->nullable();
            $table->unsignedInteger('versi')->default(1);
            $table->timestamps();
        });

        Schema::create('stock_opname_item', function (Blueprint $table) {
            $table->id();
            $table->foreignId('opname_id')->constrained('stock_opname')->cascadeOnDelete();
            $table->foreignId('batch_id')->constrained('batch_obat')->restrictOnDelete();
            $table->unsignedInteger('stok_sistem');
            $table->unsignedInteger('stok_fisik')->nullable();
            $table->integer('selisih')->nullable();
            $table->decimal('harga_beli', 15, 2)->default(0);
            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->unique(['opname_id', 'batch_id']);
        });

        Schema::create('stock_adjustment', function (Blueprint $table) {
            $table->id();
            $table->string('no_penyesuaian')->unique();
            $table->string('tipe');
            $table->date('tanggal');
            $table->string('status')->default('draft');
            $table->foreignId('stock_opname_id')->nullable()->constrained('stock_opname')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('catatan')->nullable();
            $table->timestamps();
        });

        Schema::create('stock_adjustment_item', function (Blueprint $table) {
            $table->id();
            $table->foreignId('adjustment_id')->constrained('stock_adjustment')->cascadeOnDelete();
            $table->foreignId('batch_id')->constrained('batch_obat')->restrictOnDelete();
            $table->foreignId('obat_id')->constrained('obat')->restrictOnDelete();
            $table->integer('jumlah');
            $table->text('alasan')->nullable();
            $table->decimal('nilai', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_adjustment_item');
        Schema::dropIfExists('stock_adjustment');
        Schema::dropIfExists('stock_opname_item');
        Schema::dropIfExists('stock_opname');
    }
};
