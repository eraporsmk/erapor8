<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeFieldToDudiTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dudi', function (Blueprint $table) {
            $table->dropColumn(['lintang', 'bujur']);
        });
        Schema::table('dudi', function (Blueprint $table) {
            $table->decimal('lintang', 18,12);
			$table->decimal('bujur', 18,12);
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
