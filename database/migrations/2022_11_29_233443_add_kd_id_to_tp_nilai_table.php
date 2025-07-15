<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddKdIdToTpNilaiTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("DROP VIEW IF EXISTS get_nilai_kurmer_siswa_by_kd CASCADE");
        Schema::table('tp_nilai', function (Blueprint $table) {
            $table->dropColumn('tp_id');
        });
        Schema::table('tp_nilai', function (Blueprint $table) {
            $table->uuid('tp_id')->nullable();
            $table->uuid('kd_id')->nullable();
            $table->foreign('kd_id')->references('kompetensi_dasar_id')->on('ref.kompetensi_dasar')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
        DB::statement("CREATE OR REPLACE VIEW get_nilai_kurmer_siswa_by_kd AS SELECT a.nilai, a.tp_nilai_id, a.anggota_rombel_id, b.tp_id, c.pembelajaran_id, c.rencana_penilaian_id, round(a.nilai::numeric) AS nilai_kurmer FROM nilai_tp a JOIN tp_nilai b ON b.tp_nilai_id = a.tp_nilai_id JOIN rencana_penilaian c ON c.rencana_penilaian_id = b.rencana_penilaian_id WHERE c.deleted_at IS NULL GROUP BY a.nilai, a.tp_nilai_id, a.anggota_rombel_id, b.tp_id, c.pembelajaran_id, c.rencana_penilaian_id;");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tp_nilai', function (Blueprint $table) {
            $table->dropForeign(['kd_id']);
            $table->dropColumn(['kd_id']);
        });
    }
}
