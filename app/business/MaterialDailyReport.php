<?php

namespace App\business;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use App\Models\DailyMaterial;
use Illuminate\Support\Facades\Log;


use App\Models\Setting;

class MaterialDailyReport
{
    /** 材料入庫取得by material_id, date */
    public static function GetWeightIn($material_id, $cur_date)
    {
        $enddate =  Carbon::instance($cur_date)->addDay();
        $startDateTime = Setting::instance()->getStartDateTimeOfBusinessDate($cur_date);
        $endDateTime = Setting::instance()->getStartDateTimeOfBusinessDate($enddate);

        $strSQL = 'SELECT sum(arrival_weight) weight_in ' .
            ' FROM greenearth.t_arrival_details ' .
            " WHERE crushing_status = 0 " .
            " AND arrival_date>='$startDateTime'" .
            " AND arrival_date <'$endDateTime'" .
            " AND material_id='$material_id'" .
            " GROUP BY material_id";

        // log::info($strSQL);

        $weights = DB::select($strSQL);
        $weight_in = 0;
        foreach ($weights as $weight) {
            $weight_in += $weight->weight_in;
        }

        return $weight_in;
    }

    public static function GetWeightOut($material_id, $cur_date)
    {
        $enddate =  Carbon::instance($cur_date)->addDay();
        $startDateTime = Setting::instance()->getStartDateTimeOfBusinessDate($cur_date);
        $endDateTime = Setting::instance()->getStartDateTimeOfBusinessDate($enddate);

        $strSQL = 'SELECT sum(actual_weight) weight_out ' .
            ' FROM greenearth.t_crushing_actual ' .
            " WHERE actual_date>='$startDateTime'" .
            " AND  actual_date <'$endDateTime'" .
            " AND material_id='$material_id'" .
            " GROUP BY material_id";

        $weight = DB::select($strSQL);

        $weight_out = 0;
        if (count($weight) > 0) {
            $weight_out = $weight[0]->weight_out;
        }
        return $weight_out;
    }
    /** 材料入出庫を更新する*/
    public static function doUpdate($date)
    {
        Log::info("材料入出庫処理開始...");
        DB::statement('truncate table greenearth.t_daily_material');
        DB::statement('ALTER TABLE greenearth.t_daily_material AUTO_INCREMENT = 1 ');

        $now = Carbon::now('Asia/Tokyo');
        $Materials = [];
        DB::table("m_material")
            ->orderBy("material_id")->chunk(
                100,
                function ($materials) use (&$Materials, $date, $now) {
                    foreach ($materials as $p) {
                        $material_id = $p->material_id;
                        $prevDate = Carbon::createFromFormat('Y-m-d H:i',$date.' 00:00');

                        while ($prevDate <= $now) {
                            $prevDate_str = $prevDate->format('Y-m-d');
                            $Materials[] = [
                                'material_id' => $material_id,
                                'target_date' => $prevDate_str,
                                'weight_in' => self::GetWeightIn($material_id, $prevDate),
                                'weight_out' => self::GetWeightOut($material_id, $prevDate),
                                'created_by' => 'MaterialDailyAggregate',
                                'created_at' => $now,
                                'updated_by' => 'MaterialDailyAggregate',
                                'updated_at' => $now,
                            ];
                            // log::info('Count:' . count($materials) . ', material_id:' . $material_id);
                            $prevDate->addDay();
                        }
                    }
                }
            );

        DailyMaterial::insert($Materials);

        Log::info("材料入出庫処理終了...");
    }
}
