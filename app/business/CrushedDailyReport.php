<?php

namespace App\business;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use App\Models\DailyCrushed;
use Illuminate\Support\Facades\Log;

use Exception;

use App\Models\Setting;

class CrushedDailyReport
{

    /** 粉砕済入庫取得by material_id, date */
    public static function GetWeightIn($material_id, $cur_date)
    {
        $enddate =  Carbon::instance($cur_date)->addDay();
        $startDateTime = Setting::instance()->getStartDateTimeOfBusinessDate($cur_date);
        $endDateTime = Setting::instance()->getStartDateTimeOfBusinessDate($enddate);

        $strSQL = 'SELECT sum(arrival_weight) weight_in ' .
            ' FROM greenearth.t_arrival_details ' .
            " WHERE crushing_status = 1 " .
            " AND arrival_date>='$startDateTime'" .
            " AND arrival_date <'$endDateTime'" .
            " AND material_id='$material_id'" .
            " GROUP BY material_id" .
            ' UNION ' .
            ' SELECT sum(actual_weight) weight_in ' .
            ' FROM greenearth.t_crushing_actual ' .
            " WHERE actual_date>='$startDateTime'" .
            " AND actual_date <'$endDateTime'" .
            " AND material_id='$material_id'" .
            " GROUP BY material_id";


        $weights = DB::select($strSQL);
        $weight_in = 0;
        foreach ($weights as $weight) {
            $weight_in += $weight->weight_in;
        }

        return $weight_in;
    }

    /*粉砕済出庫実績を取得する */
    public static function GetWeightOut($material_id, $cur_date)
    {
        $enddate =  Carbon::instance($cur_date)->addDay();
        $startDateTime = Setting::instance()->getStartDateTimeOfBusinessDate($cur_date);
        $endDateTime = Setting::instance()->getStartDateTimeOfBusinessDate($enddate);

        $strSQL = 'SELECT sum(blended_weight) weight_out ' .
            ' FROM greenearth.t_blender ' .
            " WHERE blended_dt>='$startDateTime'" .
            " AND  blended_dt <'$endDateTime'" .
            " AND material_id='$material_id'" .
            " GROUP BY material_id";

        $weight = DB::select($strSQL);

        $weight_out_blender = 0;
        if (count($weight) > 0) {
            $weight_out_blender = $weight[0]->weight_out;
        }

        //紛済み契約から出荷合計を取得する
        $weight_out_contract = 0;
        $dateStr = $cur_date->format('Y-m-d');
        $strSQL = 'SELECT COALESCE(sum(contract_weight),0) weight_out ' .
            ' FROM greenearth.v_contract_crushed ' .
            " WHERE material_id='$material_id' AND contract_date='$dateStr'" ;

        $weight = DB::select($strSQL);
        if (count($weight) > 0) {
            $weight_out_contract = $weight[0]->weight_out;
        }

        $weight_out = $weight_out_blender + $weight_out_contract ;
        return $weight_out;
    }

    /** 粉砕済日次入出庫情報を更新する*/
    public static function doUpdate($date)
    {

        Log::info("粉砕済入出庫処理開始...");
        DB::statement('truncate table greenearth.t_daily_crushed');
        DB::statement('ALTER TABLE greenearth.t_daily_crushed AUTO_INCREMENT = 1 ');

        $Crusheds = [];
        $now = Carbon::now('Asia/Tokyo');
        DB::table("m_material")
            ->orderBy("material_id")->chunk(
                100,
                function ($materials) use (&$Crusheds, $date, $now) {
                    foreach ($materials as $p) {

                        $material_id = $p->material_id;
                        $prevDate = Carbon::createFromFormat('Y-m-d H:i',$date.' 00:00');

                        while ($prevDate <= $now) {
                            $prevDate_str = $prevDate->format('Y-m-d');
                            $Crusheds[] = [
                                'material_id' => $material_id,
                                'target_date' => $prevDate_str,
                                'weight_in' => self::GetWeightIn($material_id, $prevDate),
                                'weight_out' => self::GetWeightOut($material_id, $prevDate),
                                'created_by' => 'CrushedDailyAggregate',
                                'created_at' => $now,
                                'updated_by' => 'CrushedDailyAggregate',
                                'updated_at' => $now,
                            ];

                            $prevDate->addDay();
                        }
                    }
                }
            );

        DailyCrushed::insert($Crusheds);
        
        Log::info("粉砕済入出庫処理終了...");
    }
}
