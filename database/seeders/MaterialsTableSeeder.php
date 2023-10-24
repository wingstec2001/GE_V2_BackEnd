<?php

namespace Database\Seeders;

use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use App\Models\Material;
use Illuminate\Support\Carbon;
class MaterialsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // ファイルのデータを取得
        $file_path = './public/image/dog.jpg';
        $data = file_get_contents($file_path);

        // データをbase64にエンコード
        $base64data = base64_encode($data);
        //
        Material::insert([
            'material_id' => Str::random(12),
            'material_name' => Str::random(12),
            'material_img' => $base64data ,
            'created_by' => 'admin',
            'updated_by' => Str::random(12),
            'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
            'updated_at' => Carbon::now()->format('Y-m-d H:i:s')
            // 'email' => str_random(10).'@gmail.com',
            // 'password' => bcrypt('secret'),
        ]);
    }
}
