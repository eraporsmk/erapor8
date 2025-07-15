<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeDeskripsiInRencanaBudayaKerjaTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('rencana_budaya_kerja', function (Blueprint $table) {
            $table->dropColumn('deskripsi');
        });
        Schema::table('rencana_budaya_kerja', function (Blueprint $table) {
            $table->text('deskripsi');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('rencana_budaya_kerja', function (Blueprint $table) {
            //
        });
    }
}
