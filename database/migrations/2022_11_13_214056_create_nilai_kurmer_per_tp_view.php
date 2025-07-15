<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNilaiKurmerPerTpView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("CREATE OR REPLACE VIEW view_nilai_kurmer_pertp AS SELECT pembelajaran_id, anggota_rombel_id, tp_id, sum(nilai) AS nilai_tp FROM get_nilai_kurmer_siswa_by_kd GROUP BY pembelajaran_id, anggota_rombel_id, tp_id;");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("DROP VIEW IF EXISTS view_nilai_kurmer_pertp CASCADE");
    }
}
