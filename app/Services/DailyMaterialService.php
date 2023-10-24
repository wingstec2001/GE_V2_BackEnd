<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use App\Models\Setting;
use App\Models\DailyMaterial;
use App\Models\DailyCrushed;
use Exception;
use App\Services\DailyCrushedService;
use Illuminate\Support\Arr;
//材料入出庫サービス
class DailyMaterialService
{
    // 材料新規・削除による在庫更新 (add,delete)
    public function updateDailyInByAddDelete($material_id, $date, $crushing_status)
    {
        $today = Setting::instance()->getBusinessDate($date);

        $enddate = Carbon::instance($today)->addDay();
        $from = Setting::instance()->getStartDateTimeOfBusinessDate($today);
        $to = Setting::instance()->getStartDateTimeOfBusinessDate($enddate);

        //材料入庫再計算
        $strSQL =
            'SELECT material_id, sum(arrival_weight) weight_in' .
            ' FROM greenearth.t_arrival_details ' .
            " WHERE arrival_date >= '$from' AND arrival_date < '$to'  " .
            " AND material_id='$material_id'" .
            " AND crushing_status = $crushing_status " .
            ' GROUP BY material_id';

        $weights = DB::select($strSQL);
        $target_date = $today->format('Y-m-d');

        $weight_in = 0;
        foreach ($weights as $weight) {
            $weight_in += $weight->weight_in;
        }

        // $material_id = $material_id;
        // $crushing_status = $crushing_status;
        if ($crushing_status == 0) {
            //材料入庫処理
            $this->updateMaterialIn($material_id, $weight_in, $target_date);
        } else {
            $this->updateCrushedDailyInByArrival($material_id, $weight_in, $date);
        }
    }

    //粉砕入荷による　粉砕入庫を更新
    private function updateCrushedDailyInByArrival($material_id, $arrival_weight, $date)
    {
        $today = Setting::instance()->getBusinessDate($date);
        $target_date = $today->format('Y-m-d');
        $crushed_weight = $this->getCrushedWeight($material_id, $date);

        $weigh_in = $crushed_weight + $arrival_weight;

        $this->updateCrushedIn($material_id, $weigh_in, $target_date);
    }

    //粉砕入荷による　粉砕入庫を更新
    private function updateCrushedDailyInByCrushed($material_id, $crushed_weight, $date)
    {
        $today = Setting::instance()->getBusinessDate($date);
        $target_date = $today->format('Y-m-d');
        $arrival_weight = $this->getArrivalCrushed($material_id, $date);

        $weigh_in = $crushed_weight + $arrival_weight;

        $this->updateCrushedIn($material_id, $weigh_in, $target_date);
    }

    //当日の粉砕済み在庫を修正　在庫量＝入荷(紛済)＋粉砕実績
    private function updateCrushedIn($material_id, $weight_in, $target_date)
    {
        $now = Carbon::now('Asia/Tokyo');
        try {
            $data = DailyCrushed::where('material_id', $material_id)
                ->where('target_date', $target_date)
                ->first();
            if ($data) {
                if ($data->weight_in != $weight_in) {
                    $data->update([
                        'weight_in' => $weight_in,
                        'updated_at' => $now,
                        'updated_by' => 'DailyMaterialService',
                    ]);
                }
            } else {
                DailyCrushed::Create([
                    'target_date' => $target_date,
                    'material_id' => $material_id,
                    'weight_in' => $weight_in,
                    'created_at' => $now,
                    'created_by' => 'DailyMaterialService',
                    'updated_at' => $now,
                    'updated_by' => 'DailyMaterialService',
                ]);
            }
        } catch (Exception $ex) {
            Log::error($ex->getMessage());
        }
    }

    //材料入庫更新処理
    private function updateMaterialIn($material_id, $weight_in, $target_date)
    {
        $now = Carbon::now('Asia/Tokyo');
        try {
            $data = DailyMaterial::where('material_id', $material_id)
                ->where('target_date', $target_date)
                ->first();

            if ($data) {
                if ($data->weight_in != $weight_in) {
                    $data->update([
                        'weight_in' => $weight_in,
                        'updated_at' => $now,
                        'updated_by' => 'DailyMaterialService',
                    ]);
                }
            } else {
                DailyMaterial::Create([
                    'target_date' => $target_date,
                    'material_id' => $material_id,
                    'weight_in' => $weight_in,
                    'created_at' => $now,
                    'created_by' => 'DailyMaterialService',
                    'updated_at' => $now,
                    'updated_by' => 'DailyMaterialService',
                ]);
            }
        } catch (Exception $ex) {
            Log::error($ex->getMessage());
        }
    }

    //日次出庫更新 粉砕済実績から合計値を出庫値として計上
    public function updateDailyOut($material_id, $date)
    {
        $today = Setting::instance()->getBusinessDate($date);
        $enddate = Carbon::instance($today)->addDay();
        $from = Setting::instance()->getStartDateTimeOfBusinessDate($today);
        $to = Setting::instance()->getStartDateTimeOfBusinessDate($enddate);

        $strSQL =
            ' SELECT material_id,sum(actual_weight) weight_out ' .
            ' FROM greenearth.t_crushing_actual ' .
            " WHERE actual_date >='$from' AND actual_date <'$to'" .
            " AND material_id='$material_id'" .
            ' GROUP BY material_id';

        $weights = DB::select($strSQL);
        if (count($weights) > 0) {
            //t_daily_materialに挿入か更新
            $weight_out = $weights[0]->weight_out;
        } else {
            $weight_out = 0;
        }

        $now = Carbon::now('Asia/Tokyo');
        $target_date = $today->format('Y-m-d');

        $data = DailyMaterial::where('material_id', $material_id)
            ->where('target_date', $target_date)
            ->first();

        if ($data) {
            if ($data->weight_out != $weight_out) {
                $data->update([
                    'weight_out' => $weight_out,
                    'updated_at' => $now,
                    'updated_by' => 'DailyMaterialService',
                ]);
            }
        } else {
            DailyMaterial::Create([
                'target_date' => $target_date,
                'material_id' => $material_id,
                'weight_out' => $weight_out,
                'created_at' => $now,
                'created_by' => 'DailyMaterialService',
                'updated_at' => $now,
                'updated_by' => 'DailyMaterialService',
            ]);
        }

        // if (!$material_exists) {
        //     $data = DailyMaterial::where('material_id', $material_id)->where('target_date', $target_date)->first();
        //     if ($data) {
        //         if ($data->weight_out != 0) {
        //             $data->update(
        //                 ['weight_out' => 0, 'updated_at' => $now, 'updated_by' => 'DailyMaterialService::updateDailyOut']
        //             );
        //         }
        //     }
        // }

        // //材料出庫⇒粉砕⇒粉砕済
        // $this->updateCrushedDailyIn($material_id,  $target_date);
    }

    //入荷粉砕の合計を取得する
    private function getArrivalCrushed($material_id, $date)
    {
        $from = Setting::instance()->getStartDateTimeOfBusinessDate($date);
        $enddate = Carbon::instance($from)->addDay();
        $to = Setting::instance()->getStartDateTimeOfBusinessDate($enddate);

        $strSQL =
            'SELECT sum(arrival_weight) weight_in ' .
            ' FROM greenearth.t_arrival_details ' .
            ' WHERE crushing_status = 1 ' .
            " AND arrival_date>='$from'" .
            " AND arrival_date <'$to'" .
            " AND material_id='$material_id'";

        $weights = DB::select($strSQL);
        $weight_in = 0;
        foreach ($weights as $weight) {
            $weight_in += $weight->weight_in;
        }

        return $weight_in;
    }

    //日次粉砕入庫更新
    public function updateCrushedDailyIn($material_id, $date)
    {
        $arrivalWeight = self::getArrivalCrushed($material_id, $date);
        $crushedWeight = self::getCrushedWeight($material_id, $date);

        // $from = Setting::instance()->getStartDateTimeOfBusinessDate($date);
        // $enddate =  Carbon::instance($from)->addDay();
        // $to = Setting::instance()->getStartDateTimeOfBusinessDate($enddate);

        // $strSQL = 'SELECT sum(arrival_weight) weight_in ' .
        // ' FROM greenearth.t_arrival_details ' .
        // " WHERE crushing_status = 1 " .
        // " AND arrival_date>='$from'" .
        // " AND arrival_date <'$to'" .
        // " AND material_id='$material_id'" .
        // " GROUP BY material_id" .
        // ' UNION ' .
        // ' SELECT sum(actual_weight) weight_in ' .
        // ' FROM greenearth.t_crushing_actual ' .
        // " WHERE actual_date>='$from'" .
        // " AND actual_date <'$to'" .
        // " AND material_id='$material_id'" .
        // " GROUP BY material_id";

        // $weights = DB::select($strSQL);
        // $weight_in = 0;
        // foreach ($weights as $weight) {
        //     $weight_in += $weight->weight_in;
        // }

        $weight_in = $arrivalWeight + $crushedWeight;
        //t_daily_materialに挿入か更新
        $now = Carbon::now('Asia/Tokyo');
        $target_date = $date;

        $data = DailyCrushed::where('material_id', $material_id)
            ->where('target_date', $target_date)
            ->first();
        if ($data) {
            if ($data->weight_in != $weight_in) {
                $data->update([
                    'weight_in' => $weight_in,
                    'updated_at' => $now,
                    'updated_by' => 'DailyMaterialService::updateCrushedDailyIn',
                ]);
            }
        } else {
            DailyCrushed::Create([
                'target_date' => $target_date,
                'material_id' => $material_id,
                'weight_in' => $weight_in,
                'created_at' => $now,
                'created_by' => 'DailyMaterialService',
                'updated_at' => $now,
                'updated_by' => 'DailyMaterialService',
            ]);
        }
    }

    //当日分の粉砕済み出庫量を集計する
    private function getCrushedWeight($material_id, $date)
    {
        $today = Setting::instance()->getBusinessDate($date);
        $enddate = Carbon::instance($today)->addDay();
        $from = Setting::instance()->getStartDateTimeOfBusinessDate($today);
        $to = Setting::instance()->getStartDateTimeOfBusinessDate($enddate);

        $strSQL =
            ' SELECT material_id,sum(actual_weight) weight_out ' .
            ' FROM greenearth.t_crushing_actual ' .
            " WHERE material_id = '$material_id' " .
            " AND actual_date >='$from'" .
            " AND actual_date <'$to'";

        $weights = DB::select($strSQL);
        $weight_out = 0;
        foreach ($weights as $weight) {
            $weight_out += $weight->weight_out;
        }

        return $weight_out;
    }

    //当日分の契約出庫量を集計する
    private function getContractWeight($material_id, $date)
    {
        $target_date = substr($date, 0, 10);
        $strSQL =
            ' SELECT sum(contract_weight) weight_out ' .
            ' FROM greenearth.v_contract_material ' . //材料契約VIEWから
            " WHERE material_id = '$material_id' " .
            " AND contract_date ='$target_date'";

        $weights = DB::select($strSQL);
        $weight_out = 0;
        foreach ($weights as $weight) {
            $weight_out += $weight->weight_out;
        }

        return $weight_out;
    }

    //当日分の出庫を合計して、DBを更新する
    public function updateWeightOut($material_id, $date, $from)
    {
        if ($from == 1) {
            //from 粉砕
            $today = Setting::instance()->getBusinessDate($date);
        } else {
            //from　契約
            $today = Setting::instance()->getBusinessDate($date . ' 12:00:00');
        }

        //当日分の契約出庫量を取得する
        $contract_weight = self::getContractWeight($material_id, $date);

        // //当日分のブレンド出庫量を取得する
        $crushed_weight = self::getCrushedWeight($material_id, $today);

        // 出庫量を合算する
        $weight_out = $contract_weight + $crushed_weight;

        //未粉砕出庫　t_daily_materialに挿入か更新
        $now = Carbon::now('Asia/Tokyo');
        $target_date = $today->format('Y-m-d');

        $data = DailyMaterial::where('material_id', $material_id)
            ->where('target_date', $target_date)
            ->first();

        if ($data) {
            $data->update([
                'weight_out' => $weight_out,
                'updated_at' => $now,
                'updated_by' => 'DailyMaterialService::updateWeightOut',
            ]);
        } else {
            DailyMaterial::Create([
                'target_date' => $target_date,
                'material_id' => $material_id,
                'weight_out' => $weight_out,
                'created_at' => $now,
                'created_by' => 'DailyMaterialService::updateWeightOut',
                'updated_at' => $now,
                'updated_by' => 'DailyMaterialService::updateWeightOut',
            ]);
        }

        //粉砕入庫を更新する
        if ($crushed_weight > 0) {
            $this->updateCrushedDailyInByCrushed($material_id, $crushed_weight, $date);
        }
    }

    //当日分の出庫を合計して、DBを更新する
}
