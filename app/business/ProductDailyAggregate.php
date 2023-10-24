<?php

namespace App\business;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use App\Models\DailyProduct;
use Exception;

use App\Models\Setting;

class ProductDailyAggregate
{
    private static $ProductLastStatDates;
    private static $defaultStartDate;

    //当日の製造分を取得する
    private static function getProductionWeight($product_id, $date)
    {
        $enddate = Carbon::instance($date)->addDay();
        $from = Setting::instance()->getStartDateTimeOfBusinessDate($date);
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

    //当日の入庫分を取得する
    private static function getArrivalWeight($product_id, $date)
    {
        $enddate = Carbon::instance($date)->addDay();
        $from = Setting::instance()->getStartDateTimeOfBusinessDate($date);
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

    /** 製品入庫取得by product_id, date */
    public static function GetWeightIn($product_id, $date)
    {
        //2023.06.14 入荷分＋生産分に変更　-->
        $productionWeight = self::getProductionWeight($product_id, $date);
        $arrivalWeight = self::getArrivalWeight($product_id, $date);
        $weight_in = $productionWeight + $arrivalWeight;

        //2023.06.14 入荷分＋生産分に変更　<--

        return $weight_in;
    }

    /*製品出庫実績を取得する */
    public static function GetWeightOut($product_id, $cur_date)
    {
        $strSQL =
            'SELECT COALESCE(sum(contract_weight),0) weight_out' .
            '   FROM greenearth.v_contract_pellet ' .
            " WHERE  product_id = '$product_id' AND contract_date='$cur_date' ";

        $weight = DB::select($strSQL);

        $weight_out = 0;
        if (count($weight) > 0) {
            $weight_out = $weight[0]->weight_out;
        }
        return $weight_out;
    }

    /** 製品日次入出庫情報を更新する*/
    public static function doUpdate()
    {
        Log::info('製品入出庫日次情報更新開始');

        $Products = [];
        DB::table('m_product')
            ->orderBy('product_id')
            ->chunk(100, function ($products) use (&$Products) {
                foreach ($products as $p) {
                    $product_id = $p->product_id;

                    $today = Carbon::today();
                    $prevDate = Carbon::create($today->year, $today->month, 1);

                    while ($prevDate <= $today) {
                        $prevDate_str = $prevDate->format('Y-m-d');
                        $Products[] = [
                            'product_id' => $product_id,
                            'target_date' => $prevDate_str,
                            'weight_in' => self::GetWeightIn($product_id, $prevDate),
                            'weight_out' => self::GetWeightOut($product_id, $prevDate_str),
                        ];

                        $prevDate->addDay();
                    }
                }
            });

        $now = Carbon::now()->format('Y-m-d H:i:s');
        foreach ($Products as $Product) {
            $product_id = $Product['product_id'];
            $target_date = $Product['target_date'];
            $weight_in = $Product['weight_in'];
            $weight_out = $Product['weight_out'];
            //t_daily_productに挿入か更新

            $data = DailyProduct::where('product_id', $product_id)
                ->where('target_date', $target_date)
                ->first();

            if ($data) {
                if ($data->weight_in != $weight_in || $data->weight_out != $weight_out) {
                    $data->update([
                        'weight_in' => $weight_in,
                        'weight_out' => $weight_out,
                        'updated_at' => $now,
                        'updated_by' => 'ProductDailyAggregate',
                    ]);
                }
            } else {
                DailyProduct::create([
                    'target_date' => $target_date,
                    'product_id' => $product_id,
                    'weight_in' => $weight_in,
                    'weight_out' => $weight_out,
                    'created_at' => $now,
                    'created_by' => 'ProductDailyAggregate',
                    'updated_at' => $now,
                    'updated_by' => 'ProductDailyAggregate',
                ]);
            }
        }
        Log::info('製品入出庫日次情報更新終了。');
    }
}
