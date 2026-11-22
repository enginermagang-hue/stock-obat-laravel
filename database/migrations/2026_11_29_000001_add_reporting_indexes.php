<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('batch_obat', function (Blueprint $table) {
            $table->index(['obat_id', 'tanggal_kedaluwarsa'], 'batch_obat_fefo_idx');
        });

        Schema::table('stock_ledger', function (Blueprint $table) {
            $table->index(['batch_id', 'tanggal_transaksi'], 'stock_ledger_batch_date_idx');
        });

        Schema::table('lplpo', function (Blueprint $table) {
            $table->index(['tahun', 'status'], 'lplpo_year_status_idx');
        });

        Schema::table('permintaan_obat', function (Blueprint $table) {
            $table->index(['facility_id', 'tanggal'], 'permintaan_facility_date_idx');
            $table->index('status', 'permintaan_status_idx');
        });

        Schema::table('distribution', function (Blueprint $table) {
            $table->index('tanggal', 'distribution_tanggal_idx');
            $table->index('permintaan_id', 'distribution_permintaan_idx');
        });

        Schema::table('distribution_item', function (Blueprint $table) {
            $table->index('permintaan_item_id', 'distribution_item_permintaan_idx');
        });

        Schema::table('goods_receipt', function (Blueprint $table) {
            $table->index('tanggal_terima', 'goods_receipt_date_idx');
            $table->index('sumber_dana_id', 'goods_receipt_sumber_dana_idx');
        });

        Schema::table('goods_receipt_item', function (Blueprint $table) {
            $table->index('obat_id', 'goods_receipt_item_obat_idx');
            $table->index('sumber_dana_id', 'goods_receipt_item_sumber_dana_idx');
        });

        Schema::table('stock_opname', function (Blueprint $table) {
            $table->index(['periode_tahun', 'periode_bulan'], 'opname_period_idx');
            $table->index(['facility_id', 'periode_tahun'], 'opname_facility_year_idx');
        });

        Schema::table('stock_opname_item', function (Blueprint $table) {
            $table->index('batch_id', 'opname_item_batch_idx');
        });

        Schema::table('prediksi_obat', function (Blueprint $table) {
            $table->index(['periode_tahun', 'periode_bulan', 'metode_model'], 'prediksi_period_method_idx');
        });

        Schema::table('rko', function (Blueprint $table) {
            $table->index(['tahun', 'status'], 'rko_year_status_idx');
        });

        Schema::table('activity_log', function (Blueprint $table) {
            $table->index('created_at', 'activity_log_created_at_idx');
            $table->index('event', 'activity_log_event_idx');
        });
    }

    public function down(): void
    {
        Schema::table('activity_log', function (Blueprint $table) {
            $table->dropIndex('activity_log_created_at_idx');
            $table->dropIndex('activity_log_event_idx');
        });

        Schema::table('rko', function (Blueprint $table) {
            $table->dropIndex('rko_year_status_idx');
        });

        Schema::table('prediksi_obat', function (Blueprint $table) {
            $table->dropIndex('prediksi_period_method_idx');
        });

        Schema::table('stock_opname_item', function (Blueprint $table) {
            $table->dropIndex('opname_item_batch_idx');
        });

        Schema::table('stock_opname', function (Blueprint $table) {
            $table->dropIndex('opname_period_idx');
            $table->dropIndex('opname_facility_year_idx');
        });

        Schema::table('goods_receipt_item', function (Blueprint $table) {
            $table->dropIndex('goods_receipt_item_obat_idx');
            $table->dropIndex('goods_receipt_item_sumber_dana_idx');
        });

        Schema::table('goods_receipt', function (Blueprint $table) {
            $table->dropIndex('goods_receipt_date_idx');
            $table->dropIndex('goods_receipt_sumber_dana_idx');
        });

        Schema::table('distribution_item', function (Blueprint $table) {
            $table->dropIndex('distribution_item_permintaan_idx');
        });

        Schema::table('distribution', function (Blueprint $table) {
            $table->dropIndex('distribution_tanggal_idx');
            $table->dropIndex('distribution_permintaan_idx');
        });

        Schema::table('permintaan_obat', function (Blueprint $table) {
            $table->dropIndex('permintaan_facility_date_idx');
            $table->dropIndex('permintaan_status_idx');
        });

        Schema::table('lplpo', function (Blueprint $table) {
            $table->dropIndex('lplpo_year_status_idx');
        });

        Schema::table('stock_ledger', function (Blueprint $table) {
            $table->dropIndex('stock_ledger_batch_date_idx');
        });

        Schema::table('batch_obat', function (Blueprint $table) {
            $table->dropIndex('batch_obat_fefo_idx');
        });
    }
};
