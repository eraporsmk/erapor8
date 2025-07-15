<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("DROP VIEW IF EXISTS get_nilai_kurmer_siswa_by_kd CASCADE");
        Schema::table('nilai_tp', function (Blueprint $table) {
            $table->dropColumn('tp_nilai_id');
        });
        Schema::table('nilai_tp', function (Blueprint $table) {
            $table->uuid('pembelajaran_id')->nullable();
            $table->uuid('tp_id')->nullable();
            $table->uuid('tp_nilai_id')->nullable();
            $table->foreign('pembelajaran_id')->references('pembelajaran_id')->on('pembelajaran')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->foreign('tp_id')->references('tp_id')->on('tujuan_pembelajaran')->onUpdate('CASCADE')->onDelete('CASCADE');
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
        Schema::table('nilai_tp', function (Blueprint $table) {
            $table->dropForeign(['pembelajaran_id']);
            $table->dropForeign(['tp_id']);
            $table->dropColumn(['pembelajaran_id', 'tp_id']);
        });
    }
};
