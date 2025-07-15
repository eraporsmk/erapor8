<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeFieldKdNilaiTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("DROP VIEW IF EXISTS get_nilai_pengetahuan_siswa_by_kd CASCADE");
        DB::statement("DROP VIEW IF EXISTS get_nilai_keterampilan_siswa_by_kd CASCADE");
        Schema::table('kd_nilai', function(Blueprint $table) {
            $table->dropColumn('id_kompetensi');
        });
        Schema::table('kd_nilai', function(Blueprint $table) {
            $table->string('id_kompetensi', 255);
        });
        DB::statement("CREATE OR REPLACE VIEW get_nilai_pengetahuan_siswa_by_kd AS SELECT a.nilai, c.bobot, a.kompetensi_id, a.anggota_rombel_id, b.kompetensi_dasar_id, c.pembelajaran_id, b.id_kompetensi, c.rencana_penilaian_id, round(a.nilai::numeric * c.bobot::numeric, 0) AS nilai_kd_pengetahuan FROM nilai a JOIN kd_nilai b ON b.kd_nilai_id = a.kd_nilai_id JOIN rencana_penilaian c ON c.rencana_penilaian_id = b.rencana_penilaian_id WHERE a.deleted_at IS NULL AND b.deleted_at IS NULL AND c.deleted_at IS NULL GROUP BY a.nilai, c.bobot, a.kompetensi_id, a.anggota_rombel_id, b.kompetensi_dasar_id, c.pembelajaran_id, b.id_kompetensi, c.rencana_penilaian_id;");
        DB::statement("CREATE OR REPLACE VIEW get_nilai_keterampilan_siswa_by_kd AS SELECT a.kompetensi_id, a.anggota_rombel_id, b.kompetensi_dasar_id, c.pembelajaran_id, b.id_kompetensi, max(c.bobot) AS bobot, max(a.nilai)::numeric * max(c.bobot)::numeric AS nilai_kd_keterampilan FROM nilai a JOIN kd_nilai b ON b.kd_nilai_id = a.kd_nilai_id JOIN rencana_penilaian c ON c.rencana_penilaian_id = b.rencana_penilaian_id WHERE a.deleted_at IS NULL AND b.deleted_at IS NULL AND c.deleted_at IS NULL GROUP BY a.kompetensi_id, a.anggota_rombel_id, b.kompetensi_dasar_id, c.pembelajaran_id, b.id_kompetensi;");

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
