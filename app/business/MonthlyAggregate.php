<?php

namespace App\business;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

use App\Models\Setting;

class MonthlyAggregate
{
    public static function getMonthBLWeight(&$getsuji, $date)
    {

        $startdate_lm = Carbon::instance($date)->addMonths(-1);

        $strSQL = " SELECT product_id,product_id, COALESCE(material_weight,0) material," .
            " COALESCE(crushed_weight,0) crushed,COALESCE(product_weight,0) product " .
            " FROM greenearth.t_getsuji_info " .
            " WHERE yyyymm = '" . $startdate_lm->format("Ym") . "'";

        $datas =  DB::select($strSQL);
        foreach ($datas as $data) {
            $getsuji[$data->product_id] = [
                'material_weight' => $data->material,
                'crushed_weight' => $data->crushed,
                'product_weight' => $data->product
            ];
        }
    }

    //材料入出庫１ヶ月の在庫量合計値を取得する
    public static function updMaterialGetsuji($from, $to, $date)
    {
        $blm_yyyymm = Carbon::instance($date)->addMonths(-1)->format("Ym");
        $target_ym = Carbon::instance($date)->format("Ym");
        $strSQL = "SELECT m.material_id,m.material_name,coalesce(lm.weight_in,0) weight_in, " .
            " COALESCE(lm.weight_out,0) weight_out, " .
            " COALESCE(g.total_weight,0) lm_weight_total " .
            " FROM greenearth.m_material m " .
            " LEFT JOIN (SELECT material_id, sum(weight_in) weight_in, sum(weight_out) weight_out " .
            "   FROM greenearth.t_daily_material  " .
            "   WHERE target_date >='$from' AND target_date <'$to' " .
            "   GROUP BY material_id) lm " .
            " ON m.material_id = lm.material_id " .
            " LEFT JOIN (SELECT material_id, total_weight FROM greenearth.t_getsuji_material " .
            " WHERE yyyymm='$blm_yyyymm') g " .
            " ON m.material_id = g.material_id " .
            " ORDER BY m.material_id";


        $datas = DB::select($strSQL);

        DB::table('t_getsuji_material')->where('yyyymm', "=", $target_ym)->delete();
        $now = Carbon::now('Asia/Tokyo');
        foreach ($datas as $data) {
            $weight = $data->lm_weight_total + $data->weight_in - $data->weight_out;

            DB::table('t_getsuji_material')->insert([
                'material_id' => $data->material_id,
                'yyyymm' => $target_ym,
                'total_weight' => $weight,
                'created_at' => $now,
                'created_by' => 'MonthlyAggregate',
                'updated_at' => $now,
                'updated_by' => 'MonthlyAggregate',
            ]);
        }
    }

    //粉砕済入出庫１ヶ月の在庫量合計値を取得する
    public static function updCrushedGetsuji($from, $to, $date)
    {
        $blm_yyyymm = Carbon::instance($date)->addMonths(-1)->format("Ym");
        $target_ym = Carbon::instance($date)->format("Ym");
        $strSQL = "SELECT m.material_id,m.material_name,coalesce(lm.weight_in,0) weight_in, " .
            " COALESCE(lm.weight_out,0) weight_out, " .
            " COALESCE(g.total_weight,0) lm_weight_total " .
            " FROM greenearth.m_material m " .
            " LEFT JOIN (SELECT material_id, sum(weight_in) weight_in, sum(weight_out) weight_out " .
            "   FROM greenearth.t_daily_crushed  " .
            "   WHERE target_date >='$from' AND target_date <'$to' " .
            "   GROUP BY material_id) lm " .
            " ON m.material_id = lm.material_id " .
            " LEFT JOIN (SELECT material_id, total_weight FROM greenearth.t_getsuji_crushed " .
            " WHERE yyyymm='$blm_yyyymm') g " .
            " ON m.material_id = g.material_id " .
            " ORDER BY m.material_id";


        $datas = DB::select($strSQL);

        DB::table('t_getsuji_crushed')->where('yyyymm', "=", $target_ym)->delete();
        $now = Carbon::now('Asia/Tokyo');
        foreach ($datas as $data) {
            $weight = $data->lm_weight_total + $data->weight_in - $data->weight_out;

            DB::table('t_getsuji_crushed')->insert([
                'material_id' => $data->material_id,
                'yyyymm' => $target_ym,
                'total_weight' => $weight,
                'created_at' => $now,
                'created_by' => 'MonthlyAggregate',
                'updated_at' => $now,
                'updated_by' => 'MonthlyAggregate',
            ]);
        }
    }

    //製品入出庫１ヶ月の在庫量合計値を取得する

    public static function updProductGetsuji($from, $to, $date)
    {
        $blm_yyyymm = Carbon::instance($date)->addMonths(-1)->format("Ym");
        $target_ym = Carbon::instance($date)->format("Ym");
        $strSQL = "SELECT m.product_id,m.product_name,coalesce(lm.weight_in,0) weight_in, " .
            " COALESCE(lm.weight_out,0) weight_out, " .
            " COALESCE(g.total_weight,0) lm_weight_total " .
            " FROM greenearth.m_product m " .
            " LEFT JOIN (SELECT product_id, sum(weight_in) weight_in, sum(weight_out) weight_out " .
            "   FROM greenearth.t_daily_product  " .
            "   WHERE target_date >='$from' AND target_date <'$to' " .
            "   GROUP BY product_id) lm " .
            " ON m.product_id = lm.product_id " .
            " LEFT JOIN (SELECT product_id, total_weight FROM greenearth.t_getsuji_product " .
            " WHERE yyyymm='$blm_yyyymm') g " .
            " ON m.product_id = g.product_id " .
            " ORDER BY m.product_id";


        $datas = DB::select($strSQL);

        DB::table('t_getsuji_product')->where('yyyymm', "=", $target_ym)->delete();
        $now = Carbon::now('Asia/Tokyo');
        foreach ($datas as $data) {
            $weight = $data->lm_weight_total + $data->weight_in - $data->weight_out;

            DB::table('t_getsuji_product')->insert([
                'product_id' => $data->product_id,
                'yyyymm' => $target_ym,
                'total_weight' => $weight,
                'created_at' => $now,
                'created_by' => 'MonthlyAggregate',
                'updated_at' => $now,
                'updated_by' => 'MonthlyAggregate',
            ]);
        }
    }
    /** 月末繰越を更新する*/
    public static function doUpdate()
    {
        Log::info('月末繰越前月情報更新開始...');


        $today = Carbon::today();
        $startdate_tm = Carbon::Create($today->year, $today->month, 1);     //本月１日取得
        $startdate_lm =  Carbon::instance($startdate_tm)->addMonths(-1);                       //先月１日取得

        $from = $startdate_lm->format("Y-m-d");
        $to =  $startdate_tm->format("Y-m-d");
        // $getsuji_blm = [];
        // self::getMonthBLWeight($getsuji_blm, $startdate_lm);

        $materials = [];
        self::updMaterialGetsuji($from, $to, $startdate_lm);
        self::updCrushedGetsuji($from, $to, $startdate_lm);
        self::updProductGetsuji($from, $to, $startdate_lm);
        // self::updMaterialGetsuji('2022-04-01', '2022-05-01', $startdate_lm);
        // self::updCrushedGetsuji('2022-04-01', '2022-05-01', $startdate_lm);
        // self::updProductGetsuji('2022-04-01', '2022-05-01', $startdate_lm);


        Log::info('月末繰越前月情報更新終了。');
    }
}
