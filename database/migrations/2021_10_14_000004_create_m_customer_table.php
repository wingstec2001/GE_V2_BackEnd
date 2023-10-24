<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMCustomerTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('m_customer', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('customer_id', 20)->nullable()->unique('customer_id_UNIQUE');
            $table->string('customer_name', 100)->nullable()->comment('取引先名（日本語、中国語等）');
            $table->string('customer_name_eng', 200)->nullable()->comment('取引先名　(英語)');
            $table->string('country_id', 10)->nullable()->index('fk_country_id_idx')->comment('国ID');
            $table->string('area_id', 20)->nullable()->comment('地域ID');
            $table->string('postcode', 45)->nullable()->comment('郵便番号');
            $table->string('address1', 100)->nullable()->comment('住所１（東京都台東区/中国湖北省武漢市）');
            $table->string('address1_eng', 200)->nullable()->comment('住所１英語()');
            $table->string('address2', 100)->nullable()->comment('住所２（漢字) (台東区上の１－３－１）');
            $table->string('adderss2_eng', 200)->nullable()->comment('住所2 英語');
            $table->string('building', 100)->nullable()->comment('建物名（千代田ビル３２１室）');
            $table->string('building_eng', 100)->nullable()->comment('建物名（英語）');
            $table->string('manager_sei', 45)->nullable()->comment('連絡人姓(佐藤・劉)');
            $table->string('manager_mei', 45)->nullable()->comment('連絡人名（一郎・大剛）');
            $table->string('manager_firstname', 45)->nullable()->comment('連絡人姓　英語');
            $table->string('manager_lastname', 45)->nullable()->comment('連絡人名　英語');
            $table->string('mobile', 45)->nullable()->unique('mobile_UNIQUE')->comment('携帯電話番号（090-xxxx-xxxx 131xxxxxxxx）');
            $table->string('email', 100)->nullable()->unique('email_UNIQUE')->comment('Email');
            $table->string('tel', 45)->nullable()->comment('固定電話番号');
            $table->string('fax', 45)->nullable()->comment('FAX');
            $table->string('wechat', 45)->nullable()->comment('Wechat');
            $table->string('line', 45)->nullable()->comment('LINE');
            $table->string('website', 100)->nullable();
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
        Schema::dropIfExists('m_customer');
    }
}
