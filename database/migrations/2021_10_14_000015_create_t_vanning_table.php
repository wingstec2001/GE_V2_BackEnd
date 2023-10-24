<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTVanningTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('t_vanning', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('vanning_id', 20)->nullable()->comment('バンニングID');
            $table->date('vanning_date')->nullable()->comment('バンニング日');
            $table->time('vanning_time')->nullable()->comment('バンニング時間');
            $table->tinyInteger('vanning_order')->nullable()->comment('積込み順位');
            $table->string('product_id', 20)->nullable()->comment('製品ID');
            $table->integer('vanning_weight')->nullable()->comment('積込み重量');
            $table->string('mark', 45)->nullable()->comment('マーク');
            $table->tinyInteger('label')->nullable()->comment('ラベル');
            $table->string('country', 45)->nullable()->comment('国名');
            $table->string('area', 45)->nullable()->comment('地域');
            $table->string('container_no', 45)->nullable()->comment('コンテナ番号');
            $table->string('seal_no', 45)->nullable()->comment('シール番号');
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
        Schema::dropIfExists('t_vanning');
    }
}
