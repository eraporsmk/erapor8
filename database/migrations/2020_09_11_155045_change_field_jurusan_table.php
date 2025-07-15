<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeFieldJurusanTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ref.jurusan', function(Blueprint $table) {
            $table->dropColumn('jenjang_pendidikan_id');
        });
        Schema::table('ref.jurusan', function(Blueprint $table) {
            $table->decimal('jenjang_pendidikan_id', 2, 0)->nullable();
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
