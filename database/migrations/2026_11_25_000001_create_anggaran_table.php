<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('anggaran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sumber_dana_id')->constrained('sumber_dana')->restrictOnDelete();
            $table->unsignedSmallInteger('tahun');
            $table->unsignedBigInteger('facility_id')->default(0);
            $table->decimal('jumlah_anggaran', 15, 2)->default(0);
            $table->string('status')->default('aktif');
            $table->unsignedInteger('versi')->default(1);
            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->unique(['tahun', 'sumber_dana_id', 'facility_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anggaran');
    }
};
