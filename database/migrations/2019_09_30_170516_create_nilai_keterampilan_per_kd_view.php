<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNilaiKeterampilanPerKdView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("CREATE OR REPLACE VIEW view_nilai_keterampilan_perkd AS SELECT kompetensi_id, anggota_rombel_id, pembelajaran_id, kompetensi_dasar_id, round(sum(nilai_kd_keterampilan) / sum(bobot)::numeric, 0) AS nilai_kd FROM get_nilai_keterampilan_siswa_by_kd GROUP BY kompetensi_id, anggota_rombel_id, pembelajaran_id, kompetensi_dasar_id;");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
		DB::statement("DROP VIEW IF EXISTS view_nilai_keterampilan_perkd CASCADE");
    }
}
