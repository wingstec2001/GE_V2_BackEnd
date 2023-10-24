<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use App\model\MedalAccessType;
use Illuminate\Support\Facades\Log;
use App\Models\Setting;
use App\Models\DailyProduct;
//ペレット入出庫サービス
class DailyProductService
{
    //ペレット日次入庫更新
    public function updateDailyInByUpdate($from_product_id, $from_produced_dt, $to_product_id, $date)
    {
        $deleteDate = Carbon::createFromFormat('Y-m-d H:i:s', $from_produced_dt);
        $deleteFrom = Setting::instance()->getStartDateTimeOfBusinessDate($deleteDate);
        $enddate = Carbon::instance($deleteDate)->addDay();
        $deleteTo = Setting::instance()->getStartDateTimeOfBusinessDate($enddate);
        $strSQL =
            'SELECT coalesce(sum(produced_weight),0) as weight_in ' .
            ' FROM greenearth.t_production ' .
            " where produced_dt >='$deleteFrom' AND produced_dt <'$deleteTo'" .
            " and product_id='$from_product_id'";

        $weights = DB::select($strSQL);

        $now = Carbon::now('Asia/Tokyo');
        $targetDate = $deleteDate->format('Y-m-d');

        foreach ($weights as $weight) {
            $product_id = $from_product_id;
            $weight_in = $weight->weight_in;
            //t_daily_productに挿入か更新
            try {
                $data = DailyProduct::where('product_id', $product_id)
                    ->where('target_date', $targetDate)
                    ->first();

                if ($data) {
                    if ($data->weight_in != $weight_in) {
                        $data->update([
                            'weight_in' => $weight_in,
                            'updated_at' => $now,
                            'updated_by' => 'DailyProductService',
                        ]);
                    }
                } else {
                    DailyProduct::Create([
                        'target_date' => $targetDate,
                        'product_id' => $product_id,
                        'weight_in' => $weight_in,
                        'created_at' => $now,
                        'created_by' => 'DailyProductService',
                        'updated_at' => $now,
                        'updated_by' => 'DailyProductService',
                    ]);
                }
            } catch (Exception $ex) {
                Log::error($ex->getMessage());
            }
        }

        //add new
        $today = Carbon::createFromFormat('Y-m-d H:i:s', $date);
        $enddate = Carbon::instance($today)->addDay();
        $from = Setting::instance()->getStartDateTimeOfBusinessDate($today);
        $to = Setting::instance()->getStartDateTimeOfBusinessDate($enddate);

        $strSQL =
            'SELECT coalesce(sum(produced_weight),0) as weight_in ' .
            ' FROM greenearth.t_production ' .
            " where produced_dt >='$from' AND produced_dt <'$to'" .
            " and product_id='$to_product_id'";

        Log::info($strSQL);

        $weights = DB::select($strSQL);

        $now = Carbon::now('Asia/Tokyo');
        $targetDate = $today->format('Y-m-d');

        foreach ($weights as $weight) {
            $product_id = $to_product_id;
            $weight_in = $weight->weight_in;
            //t_daily_productに挿入か更新
            try {
                $data = DailyProduct::where('product_id', $product_id)
                    ->where('target_date', $targetDate)
                    ->first();

                if ($data) {
                    if ($data->weight_in != $weight_in) {
                        $data->update([
                            'weight_in' => $weight_in,
                            'updated_at' => $now,
                            'updated_by' => 'DailyProductService',
                        ]);
                    }
                } else {
                    DailyProduct::Create([
                        'target_date' => $targetDate,
                        'product_id' => $product_id,
                        'weight_in' => $weight_in,
                        'created_at' => $now,
                        'created_by' => 'DailyProductService',
                        'updated_at' => $now,
                        'updated_by' => 'DailyProductService',
                    ]);
                }
            } catch (Exception $ex) {
                Log::error($ex->getMessage());
            }
        }
    }

    private function getProductionWeight($product_id, $date)
    {
        $today = Setting::instance()->getBusinessDate($date);

        $enddate = Carbon::instance($today)->addDay();
        $from = Setting::instance()->getStartDateTimeOfBusinessDate($today);
        $to = Setting::instance()->getStartDateTimeOfBusinessDate($enddate);

        $strSQL =
            'SELECT product_id, coalesce(sum(produced_weight),0) as weight_in ' .
            ' FROM greenearth.t_production ' .
            " where produced_dt >='$from' AND produced_dt <'$to'" .
            " AND product_id='$product_id'";

        //Log::info($strSQL);

        $weights = DB::select($strSQL);

        $weight_in = 0;
        foreach ($weights as $weight) {
            $weight_in = $weight->weight_in;
        }

        return $weight_in;
    }

    private function getArrivalWeight($product_id, $date)
    {
        $today = Setting::instance()->getBusinessDate($date);

        $enddate = Carbon::instance($today)->addDay();
        $from = Setting::instance()->getStartDateTimeOfBusinessDate($today);
        $to = Setting::instance()->getStartDateTimeOfBusinessDate($enddate);

        $strSQL =
            'SELECT product_id, coalesce(sum(arrival_weight),0) as weight_in ' .
            ' FROM greenearth.t_arrival_pellets ' .
            " where arrival_date >='$from' AND arrival_date <'$to'" .
            " AND product_id='$product_id'";

        $weights = DB::select($strSQL);

        $weight_in = 0;
        foreach ($weights as $weight) {
            $weight_in = $weight->weight_in;
        }

        return $weight_in;
    }

    //ペレット日次入庫更新
    public function updateDailyInByAddDelete($product_id, $date)
    {
        $productionWeight = self::getProductionWeight($product_id, $date);
        $arrivalWeight = self::getArrivalWeight($product_id, $date);

        $weight_in = $productionWeight + $arrivalWeight;

        $today = Setting::instance()->getBusinessDate($date);

        $now = Carbon::now('Asia/Tokyo');
        $targetDate = $today->format('Y-m-d');

        //t_daily_productに挿入か更新
        try {
            $data = DailyProduct::where('product_id', $product_id)
                ->where('target_date', $targetDate)
                ->first();

            if ($data) {
                if ($data->weight_in != $weight_in) {
                    $data->update([
                        'weight_in' => $weight_in,
                        'updated_at' => $now,
                        'updated_by' => 'DailyProductService',
                    ]);
                }
            } else {
                DailyProduct::Create([
                    'target_date' => $targetDate,
                    'product_id' => $product_id,
                    'weight_in' => $weight_in,
                    'created_at' => $now,
                    'created_by' => 'DailyProductService',
                    'updated_at' => $now,
                    'updated_by' => 'DailyProductService',
                ]);
            }
        } catch (Exception $ex) {
            Log::error($ex->getMessage());
        }
    }

    public function updateWeightOut($product_id, $target_date)
    {
        $strSQL =
            ' SELECT product_id,COALESCE(sum(contract_weight),0) weight_out ' .
            ' FROM greenearth.v_contract_pellet ' .
            ' WHERE contract_status < 9 ' .
            " AND  product_id ='$product_id'" .
            " AND contract_date='$target_date'";

        $weights = DB::select($strSQL);

        if (count($weights) > 0) {
            $weight_out = $weights[0]->weight_out;
        } else {
            $weight_out = 0;
        }

        //t_daily_productに挿入か更新
        $now = Carbon::now('Asia/Tokyo');

        $data = DailyProduct::where('product_id', $product_id)
            ->where('target_date', $target_date)
            ->first();

        if ($data) {
            if ($data->weight_out != $weight_out) {
                $data->update([
                    'weight_out' => $weight_out,
                    'updated_at' => $now,
                    'updated_by' => 'DailyProductService',
                ]);
            }
        } else {
            DailyProduct::Create([
                'target_date' => $target_date,
                'product_id' => $product_id,
                'weight_out' => $weight_out,
                'created_at' => $now,
                'created_by' => 'DailyProductService',
                'updated_at' => $now,
                'updated_by' => 'DailyProductService',
            ]);
        }
    }

    //ペレット日次出庫更新
    public function updateDailyOut($contract_id)
    {
        $strSQL =
            ' SELECT product_id, contract_date ' . ' FROM v_contract_pellet' . " WHERE contract_id = '$contract_id'";

        $weights = DB::select($strSQL);
        $products = [];
        foreach ($weights as $weight) {
            $products[] = [
                'product_id' => $weight->product_id,
                'target_date' => $weight->contract_date,
            ];
        }

        foreach ($products as $product) {
            $this->updateWeightOut($product['product_id'], $product['target_date']);
        }
    }

    //ペレット日次出庫更新
    public function updateDailyOutByAddDelete($target)
    {
        $product_id = $target['product_id'];
        $date = $target['target_date'];

        $this->updateWeightOut($product_id, $date);
    }
}
