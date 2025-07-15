<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNilaiAkhirKeterampilanView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("CREATE OR REPLACE VIEW view_nilai_akhir_keterampilan AS SELECT kompetensi_id, anggota_rombel_id, pembelajaran_id, round(avg(nilai_kd), 0) AS nilai_akhir FROM view_nilai_keterampilan_perkd GROUP BY kompetensi_id, anggota_rombel_id, pembelajaran_id;");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("DROP VIEW IF EXISTS view_nilai_akhir_keterampilan CASCADE");
    }
}
