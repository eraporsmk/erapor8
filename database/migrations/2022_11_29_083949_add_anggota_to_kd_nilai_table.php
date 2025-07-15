<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAnggotaToKdNilaiTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("DROP VIEW IF EXISTS get_nilai_pk_siswa_by_kd CASCADE");
        DB::statement("DROP VIEW IF EXISTS get_nilai_pengetahuan_siswa_by_kd CASCADE");
        DB::statement("DROP VIEW IF EXISTS get_nilai_keterampilan_siswa_by_kd CASCADE");
        Schema::table('kd_nilai', function (Blueprint $table) {
            $table->dropColumn('rencana_penilaian_id');
        });
        Schema::table('kd_nilai', function (Blueprint $table) {
            $table->uuid('rencana_penilaian_id')->nullable();
            $table->uuid('anggota_rombel_id')->nullable();
            $table->decimal('kompeten', 1, 0)->nullable();
            $table->foreign('anggota_rombel_id')->references('anggota_rombel_id')->on('anggota_rombel')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
        DB::statement("CREATE OR REPLACE VIEW get_nilai_pengetahuan_siswa_by_kd AS SELECT a.nilai, c.bobot, a.kompetensi_id, a.anggota_rombel_id, b.kompetensi_dasar_id, c.pembelajaran_id, b.id_kompetensi, c.rencana_penilaian_id, round(a.nilai::numeric * c.bobot::numeric, 0) AS nilai_kd_pengetahuan FROM nilai a JOIN kd_nilai b ON b.kd_nilai_id = a.kd_nilai_id JOIN rencana_penilaian c ON c.rencana_penilaian_id = b.rencana_penilaian_id WHERE a.deleted_at IS NULL AND b.deleted_at IS NULL AND c.deleted_at IS NULL GROUP BY a.nilai, c.bobot, a.kompetensi_id, a.anggota_rombel_id, b.kompetensi_dasar_id, c.pembelajaran_id, b.id_kompetensi, c.rencana_penilaian_id;");
        DB::statement("CREATE OR REPLACE VIEW get_nilai_keterampilan_siswa_by_kd AS SELECT a.kompetensi_id, a.anggota_rombel_id, b.kompetensi_dasar_id, c.pembelajaran_id, b.id_kompetensi, max(c.bobot) AS bobot, max(a.nilai)::numeric * max(c.bobot)::numeric AS nilai_kd_keterampilan FROM nilai a JOIN kd_nilai b ON b.kd_nilai_id = a.kd_nilai_id JOIN rencana_penilaian c ON c.rencana_penilaian_id = b.rencana_penilaian_id WHERE a.deleted_at IS NULL AND b.deleted_at IS NULL AND c.deleted_at IS NULL GROUP BY a.kompetensi_id, a.anggota_rombel_id, b.kompetensi_dasar_id, c.pembelajaran_id, b.id_kompetensi;");
        DB::statement("CREATE OR REPLACE VIEW get_nilai_pk_siswa_by_kd AS SELECT a.nilai, c.bobot, a.kompetensi_id, a.anggota_rombel_id, b.kompetensi_dasar_id, c.pembelajaran_id, b.id_kompetensi, c.rencana_penilaian_id, round(a.nilai::numeric * c.bobot::numeric, 0) AS nilai_kd_pk FROM nilai a JOIN kd_nilai b ON b.kd_nilai_id = a.kd_nilai_id JOIN rencana_penilaian c ON c.rencana_penilaian_id = b.rencana_penilaian_id WHERE a.deleted_at IS NULL AND b.deleted_at IS NULL AND c.deleted_at IS NULL GROUP BY a.nilai, c.bobot, a.kompetensi_id, a.anggota_rombel_id, b.kompetensi_dasar_id, c.pembelajaran_id, b.id_kompetensi, c.rencana_penilaian_id;");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('kd_nilai', function (Blueprint $table) {
            $table->dropForeign(['anggota_rombel_id']);
            $table->dropColumn(['anggota_rombel_id', 'kompeten']);
        });
    }
}
