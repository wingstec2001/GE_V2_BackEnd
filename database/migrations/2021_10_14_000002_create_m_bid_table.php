<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMBidTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('m_bid', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('bid_id', 20)->nullable()->unique('bid_id_UNIQUE')->comment('入札商品ID');
            $table->string('bid_name', 45)->nullable()->comment('入札商品名');
            $table->string('bid_desc')->nullable()->comment('入札商品説明');
            $table->integer('bid_min_price')->nullable()->comment('入札最低金額');
            $table->smallInteger('bid_max_c_cnt')->nullable()->comment('入札上限数');
            $table->binary('bid_goods_img')->nullable()->comment('入札商品イメージ');
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
        Schema::dropIfExists('m_bid');
    }
}
