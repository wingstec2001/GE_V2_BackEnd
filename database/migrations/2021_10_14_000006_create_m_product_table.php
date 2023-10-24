<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateMProductTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('m_product', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('product_id', 20)->nullable()->unique('product_id_UNIQUE')->comment('製品ID');
            $table->string('product_name', 100)->nullable()->comment('製品名');
            $table->string('product_description')->nullable()->comment('説明');
            $table->string('product_img',255)->nullable()->comment('製品イメージ');
            $table->dateTime('created_at')->nullable();
            $table->string('created_by', 45)->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->string('updated_by', 100)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('m_product');
    }
}
