<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeWilayahTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ref.mst_wilayah', function (Blueprint $table) {
            $table->dropColumn('kode_dagri');
        });
        Schema::table('ref.mst_wilayah', function (Blueprint $table) {
            $table->string('kode_dagri', 10)->nullable();
        });
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
