<?php

namespace App\business;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use App\Models\DailyCrushed;
use Illuminate\Support\Facades\Log;

use App\Models\Setting;

class CrushedDailyAggregate
{

    private static $CrushedLastStatDates;
    private static $defaultStartDate;

    /** 粉砕済入庫取得by material_id, date */
    public static function GetWeightIn($material_id, $cur_date)
    {
        $enddate =  Carbon::instance($cur_date)->addDay();
        $startDateTime = Setting::instance()->getStartDateTimeOfBusinessDate($cur_date);
        $endDateTime = Setting::instance()->getStartDateTimeOfBusinessDate($enddate);

        $strSQL = 'SELECT sum(arrival_weight) weight_in ' .
            ' FROM greenearth.t_arrival_details ' .
            " WHERE crushing_status = 1 " .
            " AND arrival_date>='" . $startDateTime . "'" .
            " AND arrival_date <'" . $endDateTime . "'" .
            " AND material_id='" . $material_id . "'" .
            " GROUP BY material_id" .
            ' UNION ' .
            ' SELECT sum(actual_weight) weight_in ' .
            ' FROM greenearth.t_crushing_actual ' .
            " WHERE actual_date>='" . $startDateTime . "'" .
            " AND actual_date <'" . $endDateTime . "'" .
            " AND material_id='" . $material_id . "'" .
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

    /** 粉砕済入出庫情報を更新する*/
    public static function doUpdate()
    {
        Log::info('粉砕済入出庫当月情報更新終了。');

        $Crusheds = [];
        DB::table("m_material")
            ->orderBy("material_id")->chunk(
                100,
                function ($cursheds) use (&$Crusheds) {
                    foreach ($cursheds as $p) {
                        $material_id = $p->material_id;

                        $today = Carbon::today();
                        $prevDate = Carbon::create($today->year, $today->month, 1);

                        while ($prevDate <= $today) {
                            $prevDate_str = $prevDate->format('Y-m-d');
                            $Crusheds[] = [
                                'material_id' => $material_id,
                                'target_date' => $prevDate_str,
                                'weight_in' => self::GetWeightIn($material_id, $prevDate),
                                'weight_out' => self::GetWeightOut($material_id, $prevDate)
                            ];

                            $prevDate->addDay();
                        }
                    }
                }
            );


        $now = Carbon::now()->format('Y-m-d H:i:s');


        foreach ($Crusheds as $Crushed) {
            $material_id = $Crushed['material_id'];
            $target_date = $Crushed['target_date'];
            $weight_in = $Crushed['weight_in'];
            $weight_out = $Crushed['weight_out'];

            $data = DailyCrushed::where('material_id', $material_id)->where('target_date', $target_date)->first();

            if ($data) {
                if ($data->weight_in != $weight_in || $data->weight_out != $weight_out) {
                    $data->update(
                        [
                            'weight_in' => $weight_in, 'weight_out' => $weight_out,
                            'updated_at' => $now, 'updated_by' => 'DailymaterialService'
                        ]
                    );
                }
            } else {
                DailyCrushed::Create(
                    [
                        'target_date' => $target_date, 'material_id' => $material_id,
                        'weight_in' => $weight_in, 'weight_out' => $weight_out,
                        'created_at' => $now, 'created_by' => 'DailymaterialService',
                        'updated_at' => $now, 'updated_by' => 'DailymaterialService'
                    ]
                );
            }
        }


        Log::info('粉砕済み入出庫当月情報更新終了。');
    }
}
