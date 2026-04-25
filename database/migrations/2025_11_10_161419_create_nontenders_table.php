<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('nontenders', function (Blueprint $table) {
            $table->id();

            $table->date('tanggal_dibuat');

            // 1. Nama Pekerjaan
            $table->string('nama_pekerjaan');

            // 2. Kode RUP
            $table->string('kode_rup');

            // 3. Pagu RUP (nominal)
            $table->bigInteger('pagu_rup');

            // 4. Kode Paket
            $table->string('kode_paket')->nullable();

            // 5. Jenis Pengadaan (dropdown)
            $table->enum('jenis_pengadaan', [
                'Barang',
                'Pekerjaan Konstruksi',
                'Jasa Konsultansi',
                'Jasa Lainnya',
                'Terintegrasi'
            ])->nullable();

            // 7. Nilai Kontrak (nominal)
            $table->decimal('nilai_kontrak', 20, 2)->nullable();

            // 8. Nilai PDN/TKDN/IMPOR (dropdown)
            $table->enum('pdn_tkdn_impor', [
                'PDN',
                'TKDN',
                'IMPOR'
            ])->nullable();

            //  Nilai  (nominal)
            $table->decimal('nilai_pdn_tkdn_impor', 20, 2)->nullable();

            // 9. Nilai UMK_Non UMK (dropdown)
            $table->enum('umk_non_umk', [
                'UMK',
                'Non UMK'
            ])->nullable();

            //  Nilai  (nominal)
            $table->decimal('nilai_umk', 20, 2)->nullable();
            $table->string('realisasi')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nontenders');
    }
};
