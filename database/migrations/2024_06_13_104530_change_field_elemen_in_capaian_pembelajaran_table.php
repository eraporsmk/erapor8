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
        Schema::table('ref.capaian_pembelajaran', function (Blueprint $table) {
            $table->dropColumn(['elemen', 'deskripsi']);
        });
        Schema::table('ref.capaian_pembelajaran', function (Blueprint $table) {
            $table->longText('elemen')->nullable();
            $table->longText('deskripsi')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ref.capaian_pembelajaran', function (Blueprint $table) {
            //
        });
    }
};
