<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTContractTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('t_contract', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('contract_id', 20)->nullable()->unique('contract_id_UNIQUE');
            $table->string('product_id', 20)->nullable()->comment('製品ID');
            $table->string('customer_id', 20)->nullable()->comment('顧客ID');
            $table->integer('contract_weight')->nullable()->comment('契約重量');
            $table->date('shpping_date')->nullable()->comment('出荷予定日');
            $table->string('contract_memo', 45)->nullable()->comment('契約メモ(予約/入札ID + 説明など)');
            $table->dateTime('created_at')->nullable();
            $table->string('created_by', 45)->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->string('updated_by', 45)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('t_contract');
    }
}
