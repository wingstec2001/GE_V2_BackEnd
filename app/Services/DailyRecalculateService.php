<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use App\Models\Setting;
use App\Models\DailyCrushed;
use App\Models\DailyMaterial;
use App\Models\DailyProduct;
use App\Models\GetsujiMaterial;
use App\Models\GetsujiCrushed;
use App\Models\GetsujiProduct;
use Exception;

//日次入出庫再計算サービス
class DailyRecalculateService
{
    //当該日付の材料入庫合計を取得する
    private function getMaterialWeightIn($material_id, $date)
    {
        $startdate = Carbon::createFromFormat('Y-m-d H:i', $date . ' 00:00');
        $enddate = Carbon::instance($startdate)->addDay();
        $startDateTime = Setting::instance()->getStartDateTimeOfBusinessDate($startdate);
        $endDateTime = Setting::instance()->getStartDateTimeOfBusinessDate($enddate);

        $strSQL =
            'SELECT COALESCE(sum(arrival_weight),0)  weight_in ' .
            ' FROM greenearth.t_arrival_details ' .
            ' WHERE crushing_status = 0 ' .
            " AND arrival_date>='$startDateTime'" .
            " AND arrival_date <'$endDateTime'" .
            " AND material_id='$material_id'";

        // log::info($strSQL);

        $weights = DB::select($strSQL);
        $weight_in = 0;
        foreach ($weights as $weight) {
            $weight_in += $weight->weight_in;
        }

        return $weight_in;
    }

    //当該日付の材料出庫合計を取得する
    private function GetMaterialOutByCrushed($material_id, $date)
    {
        $startdate = Carbon::createFromFormat('Y-m-d H:i', $date . ' 00:00');
        $enddate = Carbon::instance($startdate)->addDay();

        $startDateTime = Setting::instance()->getStartDateTimeOfBusinessDate($startdate);
        $endDateTime = Setting::instance()->getStartDateTimeOfBusinessDate($enddate);

        //粉砕による出庫
        $strSQL =
            'SELECT COALESCE(sum(actual_weight),0) weight_out ' .
            ' FROM greenearth.t_crushing_actual ' .
            " WHERE actual_date>='$startDateTime'" .
            " AND  actual_date <'$endDateTime'" .
            " AND material_id='$material_id'" .
            ' GROUP BY material_id';

        $weight = DB::select($strSQL);

        $weight_out = 0;
        if (count($weight) > 0) {
            $weight_out = $weight[0]->weight_out;
        }
        return $weight_out;
    }

    //当日分の契約出庫量を集計する
    private function getMaterialOutByContract($material_id, $date)
    {
        $strSQL =
            ' SELECT COALESCE(sum(contract_weight),0) weight_out ' .
            ' FROM greenearth.v_contract_material ' . //材料契約VIEWから
            " WHERE material_id = '$material_id' " .
            " AND contract_date ='$date'";

        $weights = DB::select($strSQL);
        $weight_out = 0;
        foreach ($weights as $weight) {
            $weight_out += $weight->weight_out;
        }

        return $weight_out;
    }

    private function GetMaterialWeightOut($material_id, $date)
    {
        $out_crushed = $this->GetMaterialOutByCrushed($material_id, $date);
        $out_contract = $this->getMaterialOutByContract($material_id, $date);

        return $out_crushed + $out_contract;
    }

    //指定月の材料入出庫を再計算する
    public function RecalculateMaterial($material_id, $target_ym, $lm_weight)
    {
        $startdate_tm = Carbon::createFromFormat('Ymd His', $target_ym . '01 000000');
        $enddate_tm = Carbon::createFromFormat('Ymd His', $target_ym . '01 000000')
            ->addMonth()
            ->addDays(-1);

        $today = Carbon::now('Asia/Tokyo');
        $to = $today->format('Y-m-d');
        if ($enddate_tm < $today) {
            $to = $enddate_tm->format('Y-m-d');
        }

        $from = $startdate_tm->startOfMonth()->format('Y-m-d');

        $startDate = Carbon::createFromFormat('Y-m-d H:i', $from . ' 00:00');
        $endDate = Carbon::createFromFormat('Y-m-d H:i', $to . ' 00:00');
        $materials = [];
        while ($startDate <= $endDate) {
            $date_str = $startDate->format('Y-m-d');
            $materials[] = [
                'material_id' => $material_id,
                'target_date' => $date_str,
                'weight_in' => $this->GetMaterialWeightIn($material_id, $date_str),
                'weight_out' => $this->GetMaterialWeightOut($material_id, $date_str),
            ];

            $startDate->addDay();
        }

        //t_daily_materialを更新する
        $weightIn_sum = 0;
        $weightOut_sum = 0;
        foreach ($materials as $material) {
            //再計算した結果をt_daily_materialに反映する
            //更新のある場合のみ、update
            $material_id = $material['material_id'];
            $target_date = $material['target_date'];
            $weight_in = $material['weight_in'];
            $weight_out = $material['weight_out'];

            $weightIn_sum += $weight_in;
            $weightOut_sum += $weight_out;

            $now = Carbon::now('Asia/Tokyo');
            DailyMaterial::updateOrcreate(
                [
                    'material_id' => $material_id,
                    'target_date' => $target_date,
                ],
                [
                    'weight_in' => $weight_in,
                    'weight_out' => $weight_out,

                    'created_at' => $now,
                    'created_by' => 'DailyRecalculateService',

                    'updated_at' => $now,
                    'updated_by' => 'DailyRecalculateService',
                ]
            );
        }

        $weight_carryover = $lm_weight + $weightIn_sum - $weightOut_sum;
        $thisMonth = $startdate_tm->format('Ym');
        //当月分の月末繰越を取得する
        $getsuji_tm = GetsujiMaterial::where('material_id', $material_id)
            ->where('yyyymm', $thisMonth)
            ->first();

        if (isset($getsuji_tm)) {
            if ($getsuji_tm->total_weight != $weight_carryover) {
                $getsuji_tm->update([
                    'total_weight' => $weight_carryover,
                    'updated_at' => Carbon::now('Asia/Tokyo'),
                    'updated_by' => 'DailyRecalculateService',
                ]);
            }
        }
        Log::info("Recalculate Material,$material_id,$from,$to");
    }

    //当日分の粉砕済み出庫量を集計する
    private function getCrushedWeight($material_id, $date)
    {
        $startdate = Carbon::createFromFormat('Y-m-d H:i', $date . ' 00:00');
        $enddate = Carbon::instance($startdate)->addDay();
        $from = Setting::instance()->getStartDateTimeOfBusinessDate($startdate);
        $to = Setting::instance()->getStartDateTimeOfBusinessDate($enddate);

        $strSQL =
            ' SELECT COALESCE(sum(actual_weight),0) weight_out ' .
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

    //入荷粉砕の合計を取得する
    private function getArrivalCrushed($material_id, $date)
    {
        $startdate = Carbon::createFromFormat('Y-m-d H:i', $date . ' 00:00');
        $enddate = Carbon::instance($startdate)->addDay();
        $from = Setting::instance()->getStartDateTimeOfBusinessDate($startdate);
        $to = Setting::instance()->getStartDateTimeOfBusinessDate($enddate);

        $strSQL =
            'SELECT COALESCE(sum(arrival_weight),0) weight_in ' .
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

    //指定日付の粉砕済の入庫を取得する
    private function GetCrushedIn($material_id, $date)
    {
        $crushed = $this->getCrushedWeight($material_id, $date);
        $arrival = $this->getArrivalCrushed($material_id, $date);

        return $crushed + $arrival;
    }

    //当日分の契約出庫量を集計する
    private function getContractCrushed($material_id, $date)
    {
        $strSQL =
            ' SELECT coalesce(sum(contract_weight),0) weight_out ' .
            ' FROM greenearth.v_contract_crushed ' .
            " WHERE material_id = '$material_id' " .
            " AND contract_date ='$date'";

        $weights = DB::select($strSQL);
        $weight_out = 0;
        foreach ($weights as $weight) {
            $weight_out += $weight->weight_out;
        }

        return $weight_out;
    }

    //当日分のブレンド（紛済出庫)量を取得する
    private function getBlended($material_id, $date)
    {
        $startdate = Carbon::createFromFormat('Y-m-d H:i', $date . ' 00:00');
        $enddate = Carbon::instance($startdate)->addDay();
        $from = Setting::instance()->getStartDateTimeOfBusinessDate($startdate);
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

    //指定日付の粉砕済出庫を取得する
    private function GetCrushedOut($material_id, $date)
    {
        $contract = $this->getContractCrushed($material_id, $date);
        $blended = $this->getBlended($material_id, $date);

        return $contract + $blended;
    }

    //指定月の粉砕済入出庫を再計算する
    public function RecalculateCrushed($material_id, $target_ym, $lm_weight)
    {
        $startdate_tm = Carbon::createFromFormat('Ymd His', $target_ym . '01 000000');
        $enddate_tm = Carbon::createFromFormat('Ymd His', $target_ym . '01 000000')
            ->addMonth()
            ->addDays(-1);

        $today = Carbon::now('Asia/Tokyo');
        $to = $today->format('Y-m-d');
        if ($enddate_tm < $today) {
            $to = $enddate_tm->format('Y-m-d');
        }

        $from = $startdate_tm->startOfMonth()->format('Y-m-d');

        $startDate = Carbon::createFromFormat('Y-m-d H:i', $from . ' 00:00');
        $endDate = Carbon::createFromFormat('Y-m-d H:i', $to . ' 00:00');
        $materials = [];
        while ($startDate <= $endDate) {
            $date_str = $startDate->format('Y-m-d');
            $materials[] = [
                'material_id' => $material_id,
                'target_date' => $date_str,
                'weight_in' => $this->GetCrushedIn($material_id, $date_str),
                'weight_out' => $this->GetCrushedOut($material_id, $date_str),
            ];

            $startDate->addDay();
        }

        //t_daily_materialを更新する
        $weightIn_sum = 0;
        $weightOut_sum = 0;
        foreach ($materials as $material) {
            //再計算した結果をt_daily_materialに反映する
            //更新のある場合のみ、update
            $material_id = $material['material_id'];
            $target_date = $material['target_date'];
            $weight_in = $material['weight_in'];
            $weight_out = $material['weight_out'];

            $weightIn_sum += $weight_in;
            $weightOut_sum += $weight_out;

            $now = Carbon::now('Asia/Tokyo');
            DailyCrushed::updateOrcreate(
                [
                    'material_id' => $material_id,
                    'target_date' => $target_date,
                ],
                [
                    'weight_in' => $weight_in,
                    'weight_out' => $weight_out,

                    'created_at' => $now,
                    'created_by' => 'DailyRecalculateService',

                    'updated_at' => $now,
                    'update_by' => 'DailyRecalculateService',
                ]
            );
        }

        $weight_carryover = $lm_weight + $weightIn_sum - $weightOut_sum;
        $thisMonth = $startdate_tm->format('Ym');
        //当月分の月末繰越を取得する
        $getsuji_tm = GetsujiCrushed::where('material_id', $material_id)
            ->where('yyyymm', $thisMonth)
            ->first();

        if (isset($getsuji_tm)) {
            if ($getsuji_tm->total_weight != $weight_carryover) {
                $getsuji_tm->update([
                    'total_weight' => $weight_carryover,
                    'updated_at' => Carbon::now('Asia/Tokyo'),
                    'updated_by' => 'DailyRecalculateService',
                ]);
            }
        }

        Log::info("Recalculate Crushed,$material_id,$from,$to");
    }

    //指定日のペレット入荷情報を取得する
    private function getArrivalPellet($product_id, $date)
    {
        $startdate = Carbon::createFromFormat('Y-m-d H:i', $date . ' 00:00');
        $enddate = Carbon::instance($startdate)->addDay();
        $from = Setting::instance()->getStartDateTimeOfBusinessDate($startdate);
        $to = Setting::instance()->getStartDateTimeOfBusinessDate($enddate);

        $strSQL =
            'SELECT COALESCE(sum(arrival_weight),0) weight_in ' .
            ' FROM greenearth.t_arrival_pellets ' .
            " WHERE product_id = '$product_id'" .
            " AND arrival_date>='$from'" .
            " AND arrival_date <'$to'";

        $weights = DB::select($strSQL);
        $weight_in = 0;
        foreach ($weights as $weight) {
            $weight_in += $weight->weight_in;
        }

        return $weight_in;
    }

    private function getProducedPellet($product_id, $date)
    {
        $startdate = Carbon::createFromFormat('Y-m-d H:i', $date . ' 00:00');
        $enddate = Carbon::instance($startdate)->addDay();
        $startDateTime = Setting::instance()->getStartDateTimeOfBusinessDate($startdate);
        $endDateTime = Setting::instance()->getStartDateTimeOfBusinessDate($enddate);

        $strSQL =
            'SELECT COALESCE(sum(produced_weight),0)  weight_in ' .
            ' FROM greenearth.t_production ' .
            " WHERE product_id= '$product_id'" .
            " AND produced_dt>='$startDateTime'" .
            " AND produced_dt <'$endDateTime'";

        // log::info($strSQL);

        $weights = DB::select($strSQL);
        $weight_in = 0;
        foreach ($weights as $weight) {
            $weight_in += $weight->weight_in;
        }

        return $weight_in;
    }

    //指定のペレット入庫を取得する
    private function getPelletIn($product_id, $date)
    {
        $produced = $this->getProducedPellet($product_id, $date);
        $arrival = $this->getArrivalPellet($product_id, $date);

        return $produced + $arrival;
    }

    private function getPelletOut($product_id, $date)
    {
        $strSQL =
            ' SELECT coalesce(sum(contract_weight),0) weight_out ' .
            ' FROM greenearth.v_contract_pellet ' .
            " WHERE product_id = '$product_id' " .
            " AND contract_date ='$date'";

        $weights = DB::select($strSQL);
        $weight_out = 0;
        foreach ($weights as $weight) {
            $weight_out += $weight->weight_out;
        }

        return $weight_out;
    }

    //指定月の粉砕済入出庫を再計算する
    public function RecalculatePellet($product_id, $target_ym, $lm_weight)
    {
        $startdate_tm = Carbon::createFromFormat('Ymd His', $target_ym . '01 000000');
        $enddate_tm = Carbon::createFromFormat('Ymd His', $target_ym . '01 000000')
            ->addMonth()
            ->addDays(-1);

        $today = Carbon::now('Asia/Tokyo');
        $to = $today->format('Y-m-d');
        if ($enddate_tm < $today) {
            $to = $enddate_tm->format('Y-m-d');
        }

        $from = $startdate_tm->startOfMonth()->format('Y-m-d');

        $startDate = Carbon::createFromFormat('Y-m-d H:i', $from . ' 00:00');
        $endDate = Carbon::createFromFormat('Y-m-d H:i', $to . ' 00:00');
        $materials = [];
        while ($startDate <= $endDate) {
            $date_str = $startDate->format('Y-m-d');
            $materials[] = [
                'product_id' => $product_id,
                'target_date' => $date_str,
                'weight_in' => $this->GetPelletIn($product_id, $date_str),
                'weight_out' => $this->GetPelletOut($product_id, $date_str),
            ];

            $startDate->addDay();
        }

        //t_daily_materialを更新する
        $weightIn_sum = 0;
        $weightOut_sum = 0;
        foreach ($materials as $material) {
            //再計算した結果をt_daily_materialに反映する
            //更新のある場合のみ、update
            $product_id = $material['product_id'];
            $target_date = $material['target_date'];
            $weight_in = $material['weight_in'];
            $weight_out = $material['weight_out'];

            $weightIn_sum += $weight_in;
            $weightOut_sum += $weight_out;

            $now = Carbon::now('Asia/Tokyo');
            DailyProduct::updateOrcreate(
                [
                    'product_id' => $product_id,
                    'target_date' => $target_date,
                ],
                [
                    'weight_in' => $weight_in,
                    'weight_out' => $weight_out,

                    'created_at' => $now,
                    'created_by' => 'DailyRecalculateService',

                    'updated_at' => $now,
                    'updated_by' => 'DailyRecalculateService',
                ]
            );
        }

        $weight_carryover = $lm_weight + $weightIn_sum - $weightOut_sum;

        $thisMonth = $startdate_tm->format('Ym');
        //当月分の月末繰越を取得する
        $getsuji_tm = GetsujiProduct::where('product_id', $product_id)
            ->where('yyyymm', $thisMonth)
            ->first();

        if (isset($getsuji_tm)) {
            if ($getsuji_tm->total_weight != $weight_carryover) {
                $getsuji_tm->update([
                    'total_weight' => $weight_carryover,
                    'updated_at' => Carbon::now('Asia/Tokyo'),
                    'updated_by' => 'DailyRecalculateService',
                ]);
            }
        }
        Log::info("Recalculate pellet,$product_id,$from,$to");
    }
}
