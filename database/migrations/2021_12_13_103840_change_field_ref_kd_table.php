<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeFieldRefKdTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ref.kompetensi_dasar', function(Blueprint $table) {
            $table->dropColumn('id_kompetensi');;
        });
        Schema::table('ref.kompetensi_dasar', function(Blueprint $table) {
            $table->text('id_kompetensi');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        
    }
}
