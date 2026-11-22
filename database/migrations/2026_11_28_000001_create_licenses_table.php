<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('licenses', function (Blueprint $table) {
            $table->id();
            $table->string('jenis');
            $table->string('nomor');
            $table->date('tanggal_terbit')->nullable();
            $table->date('tanggal_kedaluwarsa')->nullable();
            $table->string('file_path')->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->index(['jenis', 'tanggal_kedaluwarsa']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('licenses');
    }
};
