<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Customer::create([
            'country_id'=>'JPN',
            'customer_id'=>'123', 
            'customer_name'=>'○○××会社', 
            'customer_name_eng'=>'○○××　company',
            'area_id'=>'1', 
            'postcode'=>'100000',
            'address1'=>'東京都台東区',
            'address1_eng'=>'tokyo taitoku',
            'address2'=>'台東区１－３－１',
            'adderss2_eng'=>'taitoku 1-3-1', 
            'building'=>' ３２１室',
            'building_eng'=>'room 321',
            'manager_sei'=>'佐藤', 
            'manager_mei'=>'大剛', 
            'manager_firstname'=>'taigo', 
            'manager_lastname'=>'sato',
            'mobile'=>'07045454782', 
            'email'=>'taigo@gmail.com', 
            'tel'=>'0805456124', 
            'fax'=>'01578964', 
            'website'=>'www.tokyo.com',
            'created_by'=>'admin', 
            'updated_by'=>'tokyo taitoku', 
        ]);
    }
}
