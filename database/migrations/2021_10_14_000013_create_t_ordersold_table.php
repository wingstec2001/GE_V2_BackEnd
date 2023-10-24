<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTOrdersoldTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('t_ordersold', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('reserve_id', 20)->nullable();
            $table->string('customer_id', 20)->nullable();
            $table->integer('order_weight')->nullable();
            $table->dateTime('order_dt')->nullable();
            $table->string('created_by', 45)->nullable();
            $table->dateTime('created_at')->nullable();
            $table->string('updated_by', 45)->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->string('t_orderscol', 45)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('t_ordersold');
    }
}
