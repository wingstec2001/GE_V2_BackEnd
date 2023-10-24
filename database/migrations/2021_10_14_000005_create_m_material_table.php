<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
class CreateMMaterialTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('m_material', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('material_id', 20)->nullable()->unique('mat_id_UNIQUE')->comment('原料ID');
            $table->string('material_name', 100)->nullable()->comment('原料名');
            $table->string('material_img', 255)->nullable()->comment('原料イメージ');
            $table->string('material_note', 255)->nullable()->comment('原料ノート');
            // $table->dateTime('created_at')->nullable();
            $table->string('created_by', 45)->nullable();
            // $table->dateTime('updated_at')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
        });
        // DB::statement("ALTER TABLE m_material MODIFY COLUMN material_img MEDIUMBLOB");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('m_material');
    }
}
