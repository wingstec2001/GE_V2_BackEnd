<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTAuctionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('t_auctions', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('bid_id', 20)->nullable()->index('fk_auctions_bid_idx')->comment('入札商品ID');
            $table->string('customer_id', 20)->nullable()->index('fk_auctions_customer_idx')->comment('取引先ID');
            $table->dateTime('bid_dt')->nullable()->comment('入札日時');
            $table->integer('bid_price')->nullable()->comment('入札金額
');
            $table->tinyInteger('bid_result')->nullable()->comment('入札結果');
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
        Schema::dropIfExists('t_auctions');
    }
}
