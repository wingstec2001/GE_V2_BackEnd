<?php

namespace Database\Seeders;
use Illuminate\Support\Facades\DB;

use Illuminate\Database\Seeder;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('users')->insert([
            'id'  => '1',
            'name'     => 'super',
            'email'    => 'super@gmail.com',
            'password' => bcrypt('abcd1234'),
        ]);
        DB::table('users')->insert([
            'id'  => '2',
            'name'     => 'adm',
            'email'    => 'adm@gmail.com',
            'password' => bcrypt('abcd1234'),
        ]);
        DB::table('users')->insert([
            'id'  => '3',
            'name'     => 'stuff',
            'email'    => 'stuff@gmail.com',
            'password' => bcrypt('abcd1234'),
        ]);
    }
}
