<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTCrusingplanTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('t_crusingplan', function (Blueprint $table) {
            $table->integer('id', true);
            $table->tinyInteger('route_id')->nullable()->default(1)->unique('route_id_UNIQUE')->comment('ルーターID (1 or 2)');
            $table->date('plan_date')->nullable()->comment('計画日');
            $table->string('material_id', 20)->nullable()->index('material_id_idx')->comment('原料ID');
            $table->integer('plan_wight')->nullable()->comment('粉砕予定量');
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
        Schema::dropIfExists('t_crusingplan');
    }
}
