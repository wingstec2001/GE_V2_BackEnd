<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMReserveTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('m_reserve', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('reserve_id', 20)->nullable()->unique('reserve_id_UNIQUE')->comment('予約商品ID');
            $table->string('product_id', 10)->nullable()->index('product_id_idx')->comment('製品ID');
            $table->string('reserve_name', 100)->nullable()->comment('予約商品名');
            $table->integer('reserve_weight')->nullable();
            $table->string('reserve_desc')->nullable()->comment('予約商品説明');
            $table->smallInteger('reserve_maximum')->nullable()->default(10)->comment('予約商品上限数(0:無制限)');
            $table->dateTime('reserve_open_dt')->nullable()->comment('公開開始日');
            $table->dateTime('reserve_comp_dt')->nullable()->comment('予定締め切り日');
            $table->string('created_by', 45)->nullable();
            $table->dateTime('created_at')->nullable();
            $table->string('updated_by', 45)->nullable();
            $table->dateTime('updated_at')->nullable()->comment('重量');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('m_reserve');
    }
}
