<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeysToTCrusingplanTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('t_crusingplan', function (Blueprint $table) {
            $table->foreign(['material_id'], 'material_id')->references(['material_id'])->on('m_material')->onUpdate('NO ACTION')->onDelete('NO ACTION');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('t_crusingplan', function (Blueprint $table) {
            $table->dropForeign('material_id');
        });
    }
}
