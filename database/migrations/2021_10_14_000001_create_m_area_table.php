<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMAreaTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('m_area', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('area_id', 20)->nullable()->unique('area_id_UNIQUE');
            $table->string('area_name', 45)->nullable();
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
        Schema::dropIfExists('m_area');
    }
}
