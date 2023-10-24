<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeysToTOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('t_orders', function (Blueprint $table) {
            $table->foreign(['customer_id'], 'fk_t_orders_customer')->references(['customer_id'])->on('m_customer')->onUpdate('NO ACTION')->onDelete('NO ACTION');
            $table->foreign(['reserve_id'], 'fk_t_orders_reserve')->references(['reserve_id'])->on('m_reserve')->onUpdate('NO ACTION')->onDelete('NO ACTION');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('t_orders', function (Blueprint $table) {
            $table->dropForeign('fk_t_orders_customer');
            $table->dropForeign('fk_t_orders_reserve');
        });
    }
}
