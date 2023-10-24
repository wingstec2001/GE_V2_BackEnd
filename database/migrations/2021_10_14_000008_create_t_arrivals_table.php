<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTArrivalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('t_arrivals', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('arrival_id', 20)->nullable()->unique('arrival_id_UNIQUE')->comment('入荷ID');
            $table->string('material_id', 20)->nullable()->comment('原料ID');
            $table->string('customer_id', 20)->nullable()->comment('取引先ID');
            $table->dateTime('plan_date')->nullable()->comment('入荷予定日');
            $table->string('updated_by', 45)->nullable();
            $table->tinyInteger('plan_ampm')->nullable()->comment('午前午後(0:午前　1:午後)');
            $table->integer('plan_weight')->nullable();
            $table->dateTime('actual_date')->nullable()->comment('実際入荷日\n');
            $table->tinyInteger('actual_ampm')->nullable()->comment('実際入荷時( 0:午前 1:午後)');
            $table->integer('actual_weight')->nullable();
            $table->string('created_by', 45)->nullable();
            $table->dateTime('created_at')->nullable();
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
        Schema::dropIfExists('t_arrivals');
    }
}
