<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNilaiAkhirKurmerView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("CREATE OR REPLACE VIEW view_nilai_akhir_kurmer AS SELECT pembelajaran_id, anggota_rombel_id, round(avg(nilai_tp), 0) AS nilai_akhir FROM view_nilai_kurmer_pertp GROUP BY pembelajaran_id, anggota_rombel_id;");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("DROP VIEW IF EXISTS view_nilai_akhir_kurmer CASCADE");
    }
}
