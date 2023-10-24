<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use App\Models\Setting;
use App\Models\DailyCrushed;
use Exception;

//粉砕済入出庫サービス
class DailyCrushedService
{
    //当日分の契約出庫量を集計する
    private function getContractWeight($material_id, $date)
    {
        $target_date = substr($date, 0, 10);
        $strSQL =
            ' SELECT coalesce(contract_weight,0) weight_out ' .
            ' FROM greenearth.v_contract_crushed ' .
            " WHERE material_id = '$material_id' " .
            " AND contract_date ='$target_date'";

        $weights = DB::select($strSQL);
        $weight_out = 0;
        foreach ($weights as $weight) {
            $weight_out += $weight->weight_out;
        }

        return $weight_out;
    }

    //当日分のブレンド出庫量を集計する
    private function getBlendedWeight($material_id, $date)
    {
        //当日分のブレンド出庫量を取得する
        $today = Setting::instance()->getBusinessDate($date);
        $enddate = Carbon::instance($today)->addDay();
        $from = Setting::instance()->getStartDateTimeOfBusinessDate($today);
        $to = Setting::instance()->getStartDateTimeOfBusinessDate($enddate);

        $strSQL =
            ' SELECT material_id,sum(blended_weight) weight_out ' .
            ' FROM greenearth.t_blender ' .
            " WHERE material_id = '$material_id' " .
            " AND blended_dt >='$from'" .
            " AND blended_dt <'$to'";

        $weights = DB::select($strSQL);
        $weight_out = 0;
        foreach ($weights as $weight) {
            $weight_out += $weight->weight_out;
        }

        return $weight_out;
    }

    //粉砕出庫日次出庫更新 blenderの実績による
    public function updateWeightOut($material_id, $date, $from)
    {
        if ($from == 1) {
            //Blender空
            $today = Setting::instance()->getBusinessDate($date);
        } else {
            $today = Setting::instance()->getBusinessDate($date . ' 12:00:00');
        }
        // $enddate =  Carbon::instance($today)->addDay();
        // $from = Setting::instance()->getStartDateTimeOfBusinessDate($today);
        // $to = Setting::instance()->getStartDateTimeOfBusinessDate($enddate);

        // $strSQL = " SELECT material_id,sum(blended_weight) weight_out " .
        //     " FROM greenearth.t_blender " .
        //     " WHERE material_id = '$material_id' " .
        //     " AND blended_dt >='$from'" .
        //     " AND blended_dt <'$to'";

        // $weights = DB::select($strSQL);
        // $weight_out = 0;
        // foreach ($weights as $weight) {
        //     $weight_out += $weight->weight_out;
        // }

        //当日分の契約出庫量を取得する
        $contract_weight = self::getContractWeight($material_id, $date);

        // //当日分のブレンド出庫量を取得する
        $blended_weight = self::getBlendedWeight($material_id, $today);

        // 出庫量を合算する
        $weight_out = $contract_weight + $blended_weight;

        //t_daily_materialに挿入か更新
        $now = Carbon::now('Asia/Tokyo');
        $target_date = $today->format('Y-m-d');

        $data = DailyCrushed::where('material_id', $material_id)
            ->where('target_date', $target_date)
            ->first();

        if ($data) {
            $data->update([
                'weight_out' => $weight_out,
                'updated_at' => $now,
                'updated_by' => 'DailyCrushedService::updateWeightOut',
            ]);
        } else {
            DailyCrushed::Create([
                'target_date' => $target_date,
                'material_id' => $material_id,
                'weight_out' => $weight_out,
                'created_at' => $now,
                'created_by' => 'DailyCrushedService::updateWeightOut',
                'updated_at' => $now,
                'updated_by' => 'DailyCrushedService::updateWeightOut',
            ]);
        }
    }

    //日次出庫更新 契約による
    public function updateDailyOutByContract($material_id, $date, $contract_weight)
    {
        $today = Setting::instance()->getBusinessDate($date);

        $enddate = Carbon::instance($today)->addDay();
        $from = Setting::instance()->getStartDateTimeOfBusinessDate($today);
        $to = Setting::instance()->getStartDateTimeOfBusinessDate($enddate);

        $strSQL =
            ' SELECT material_id,sum(contract_weight) weight_out ' .
            ' FROM greenearth.v_contract_crushed ' .
            " WHERE material_id = '$material_id' " .
            " AND contract_date ='$date'";

        $weights = DB::select($strSQL);
        $weight_out = 0;
        foreach ($weights as $weight) {
            $weight_out += $weight->weight_out;
        }

        //2022.07.25 紛済み出荷時の契約重量も計算する
        $weight_out += $contract_weight;

        //t_daily_materialに挿入か更新
        $now = Carbon::now('Asia/Tokyo');
        $target_date = $today->format('Y-m-d');

        $data = DailyCrushed::where('material_id', $material_id)
            ->where('target_date', $target_date)
            ->first();

        if ($data) {
            $data->update([
                'weight_out' => $weight_out,
                'updated_at' => $now,
                'updated_by' => 'DailyCrushedService::updateWeightOut',
            ]);
        } else {
            DailyCrushed::Create([
                'target_date' => $target_date,
                'material_id' => $material_id,
                'weight_out' => $weight_out,
                'created_at' => $now,
                'created_by' => 'DailyCrushedService::updateWeightOut',
                'updated_at' => $now,
                'updated_by' => 'DailyCrushedService::updateWeightOut',
            ]);
        }
    }

    //紛済み日次出庫更新
    public function updateDailyOut($contract_id)
    {
        $strSQL =
            ' SELECT material_id, contract_date, contract_weight ' .
            ' FROM v_contract_crushed' .
            " WHERE contract_id = '$contract_id'";

        $weights = DB::select($strSQL);
        $materials = [];
        foreach ($weights as $weight) {
            $materials[] = [
                'material_id' => $weight->material_id,
                'target_date' => $weight->contract_date . ' 12:00:00',
                'contract_weight' => $weight->contract_weight,
            ];
        }

        foreach ($materials as $material) {
            $this->updateDailyOutByContract(
                $material['material_id'],
                $material['target_date'],
                $material['contract_weight']
            );
        }
    }

    //紛済み日次出庫更新
    public function updateDailyOutByAddDelete($targets)
    {
        foreach ($targets as $target) {
            $material_id = $target['material_id'];
            $date = $target['target_date'];
            $contract_weight = $target['contract_weight'];

            $this->updateDailyOutByContract($material_id, $date, $contract_weight);
        }
    }
}
