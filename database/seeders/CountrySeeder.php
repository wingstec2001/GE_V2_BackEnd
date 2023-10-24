<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Country;
class CountrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Country::insert([
            'country_id'=>'JPN',
            'county_name'=>'日本',
            'country_code'=>'+81',
            'country_name_eng'=>'JAPAN',
        ]);
        Country::insert([
            'country_id'=>'CHN',
            'county_name'=>'中国',
            'country_code'=>'+86',
            'country_name_eng'=>'CHINA',
        ]);
    }
}
