<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNilaiPkPerKdView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("CREATE OR REPLACE VIEW view_nilai_pk_perkd AS SELECT pembelajaran_id, anggota_rombel_id, kompetensi_id, kompetensi_dasar_id, sum(bobot) AS bobot, sum(nilai) AS jml_nilai, round(sum(nilai_kd_pk) / sum(bobot)::numeric, 0) AS nilai_kd FROM get_nilai_pk_siswa_by_kd GROUP BY pembelajaran_id, anggota_rombel_id, kompetensi_id, kompetensi_dasar_id;");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("DROP VIEW IF EXISTS view_nilai_pk_perkd CASCADE");
    }
}
