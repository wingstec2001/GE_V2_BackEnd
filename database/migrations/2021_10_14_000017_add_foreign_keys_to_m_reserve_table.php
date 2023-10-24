<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeysToMReserveTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('m_reserve', function (Blueprint $table) {
            $table->foreign(['product_id'], 'fk_reserve_product')->references(['product_id'])->on('m_product')->onUpdate('NO ACTION')->onDelete('NO ACTION');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('m_reserve', function (Blueprint $table) {
            $table->dropForeign('fk_reserve_product');
        });
    }
}
