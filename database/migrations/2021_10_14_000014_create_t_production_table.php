<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTProductionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('t_production', function (Blueprint $table) {
            $table->integer('id', true);
            $table->tinyInteger('route_id')->nullable()->unique('route_id_UNIQUE')->comment('ルーターID (1 or 2)');
            $table->string('product_id', 20)->nullable()->comment('製品ID');
            $table->dateTime('produced_dt')->nullable()->comment('生産日時');
            $table->integer('produced_weight')->nullable()->comment('生産量');
            $table->string('created_by', 45)->nullable();
            $table->dateTime('created_at')->nullable();
            $table->string('updated_by', 45)->nullable();
            $table->dateTime('updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('t_production');
    }
}
