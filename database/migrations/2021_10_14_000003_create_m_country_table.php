<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMCountryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('m_country', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('country_id', 10)->nullable()->unique('country_id_UNIQUE')->comment('国ID（JPN,CHN,USA）');
            $table->string('country_name', 40)->nullable()->comment('国名');
            $table->string('country_code', 10)->nullable()->comment('国番号　(+81,+86)');
            $table->string('country_name_eng', 40)->nullable();
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
        Schema::dropIfExists('m_country');
    }
}
