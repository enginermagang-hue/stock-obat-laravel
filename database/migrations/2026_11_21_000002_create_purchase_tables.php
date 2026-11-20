<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_order', function (Blueprint $table) {
            $table->id();
            $table->string('no_po')->unique();
            $table->foreignId('supplier_id')->constrained('supplier')->restrictOnDelete();
            $table->foreignId('sumber_dana_id')->constrained('sumber_dana')->restrictOnDelete();
            $table->string('no_dokumen_anggaran')->nullable();
            $table->date('tanggal_po');
            $table->string('status')->default('draft');
            $table->decimal('total', 15, 2)->default(0);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('alasan_override_pagu')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('catatan')->nullable();
            $table->timestamps();
        });

        Schema::create('purchase_order_item', function (Blueprint $table) {
            $table->id();
            $table->foreignId('po_id')->constrained('purchase_order')->cascadeOnDelete();
            $table->foreignId('obat_id')->constrained('obat')->restrictOnDelete();
            $table->unsignedInteger('jumlah');
            $table->decimal('harga_beli', 15, 2);
            $table->string('no_batch')->nullable();
            $table->date('tanggal_kedaluwarsa')->nullable();
            $table->unsignedInteger('jumlah_diterima')->default(0);
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('goods_receipt', function (Blueprint $table) {
            $table->id();
            $table->string('no_penerimaan')->unique();
            $table->foreignId('po_id')->constrained('purchase_order')->restrictOnDelete();
            $table->foreignId('supplier_id')->constrained('supplier')->restrictOnDelete();
            $table->foreignId('sumber_dana_id')->constrained('sumber_dana')->restrictOnDelete();
            $table->date('tanggal_terima');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('catatan')->nullable();
            $table->timestamps();
        });

        Schema::create('goods_receipt_item', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receipt_id')->constrained('goods_receipt')->cascadeOnDelete();
            $table->foreignId('po_item_id')->nullable()->constrained('purchase_order_item')->nullOnDelete();
            $table->foreignId('obat_id')->constrained('obat')->restrictOnDelete();
            $table->string('no_batch');
            $table->date('tanggal_produksi')->nullable();
            $table->date('tanggal_kedaluwarsa');
            $table->unsignedInteger('jumlah');
            $table->decimal('harga_beli', 15, 2);
            $table->string('lokasi_rak')->nullable();
            $table->foreignId('sumber_dana_id')->constrained('sumber_dana')->restrictOnDelete();
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_receipt_item');
        Schema::dropIfExists('goods_receipt');
        Schema::dropIfExists('purchase_order_item');
        Schema::dropIfExists('purchase_order');
    }
};
