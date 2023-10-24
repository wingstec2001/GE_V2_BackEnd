<?php

namespace App\business;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use App\Models\DailyMaterial;
use Exception;

use App\Models\Setting;

class MaterialDailyAggregate
{

    private static $MaterialLastStatDates;
    private static $defaultStartDate;

    public static function InitLastStatDates()
    {
        self::$MaterialLastStatDates = [];
        $materialDailies = DB::select("SELECT material_id, max(target_date) AS stat_date FROM t_daily_material GROUP BY material_id");
        foreach ($materialDailies as $cd) {
            self::$MaterialLastStatDates[$cd->material_id] = new Carbon($cd->stat_date);
        }

        self::$defaultStartDate = Carbon::createFromFormat('Y-m-d His', Setting::instance()->getNichijiStartDate() . ' 000000');
    }


    /** 材料入庫取得by material_id, date */
    public static function GetWeightIn($material_id, $cur_date)
    {
        $enddate =  Carbon::instance($cur_date)->addDay();
        $startDateTime = Setting::instance()->getStartDateTimeOfBusinessDate($cur_date);
        $endDateTime = Setting::instance()->getStartDateTimeOfBusinessDate($enddate);

        $strSQL = 'SELECT sum(arrival_weight) weight_in ' .
            ' FROM greenearth.t_arrival_details ' .
            " WHERE crushing_status = 0 " .
            " AND arrival_date>='" . $startDateTime . "'" .
            " AND arrival_date <'" . $endDateTime . "'" .
            " AND material_id='" . $material_id . "'" .
            " GROUP BY material_id";

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
            " WHERE actual_date>='" . $startDateTime . "'" .
            " AND  actual_date <'" . $endDateTime . "'" .
            " AND material_id='" . $material_id . "'" .
            " GROUP BY material_id";

        $weight = DB::select($strSQL);

        $weight_out = 0;
        if (count($weight) > 0) {
            $weight_out = $weight[0]->weight_out;
        }
        return $weight_out;
    }
    /** 材料入出庫を更新する*/
    public static function doUpdate()
    {
        Log::info('材料入出庫当月情報更新開始');
        self::InitLastStatDates();

        $Materials = [];
  
        DB::table("m_material")
            ->orderBy("material_id")->chunk(
                100,
                function ($materials) use (&$Materials) {
                    foreach ($materials as $p) {
                        $material_id = $p->material_id;

                        $today = Carbon::today();
                        $prevDate = Carbon::create($today->year, $today->month, 1);

                        while ($prevDate <= $today) {
                            $prevDate_str = $prevDate->format('Y-m-d');
                            $Materials[] = [
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


        $now = Carbon::now('Asia/Tokyo');
        foreach ($Materials as $material) {
            $material_id =  $material['material_id'];
            $target_date = $material['target_date'];
            $weight_in = $material['weight_in'];
            $weight_out = $material['weight_out'];
            $data = DailyMaterial::where('material_id', $material_id)->where('target_date', $target_date)->first();

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
                DailyMaterial::Create(
                    [
                        'target_date' => $target_date, 'material_id' => $material_id,
                        'weight_in' => $weight_in, 'weight_out' => $weight_out,
                        'created_at' => $now, 'created_by' => 'DailymaterialService',
                        'updated_at' => $now, 'updated_by' => 'DailymaterialService'
                    ]
                );
            }
        }

        Log::info('材料入出庫当月情報更新終了。');
    }
}
