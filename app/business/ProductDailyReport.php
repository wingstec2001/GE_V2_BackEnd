<?php

namespace App\business;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use App\Models\DailyProduct;

use Exception;

use App\Models\Setting;

class ProductDailyReport
{

    private static $ProductLastStatDates;
    private static $defaultStartDate;

    /** 粉砕済日次入出庫の当該製品の前回対象日を取得 */
    public static function InitLastStatDates()
    {
        self::$ProductLastStatDates = [];
        $ProductDailies = DB::select("SELECT product_id, max(target_date) AS stat_date FROM t_daily_product GROUP BY product_id");
        foreach ($ProductDailies as $cd) {
            self::$ProductLastStatDates[$cd->product_id] = new Carbon($cd->stat_date);
        }

        self::$defaultStartDate = Carbon::createFromFormat('Y-m-d His', Setting::instance()->getNichijiStartDate() . ' 000000');
    }

    /** 当該製品の日次処理対象日を取得、DB存在しない場合default対象日を使う */
    public static function GetProductDailyLastDate($productId)
    {
        $ret = self::$ProductLastStatDates[$productId] ?? self::$defaultStartDate;

        return $ret;
    }


    /** 粉砕済入庫取得by product_id, date */
    public static function GetWeightIn($product_id, $cur_date)
    {
        $enddate =  Carbon::instance($cur_date)->addDay();
        $startDateTime = Setting::instance()->getStartDateTimeOfBusinessDate($cur_date);
        $endDateTime = Setting::instance()->getStartDateTimeOfBusinessDate($enddate);

        $strSQL = 'SELECT sum(produced_weight) weight_in ' .
            ' FROM greenearth.t_production ' .
            " WHERE produced_dt >='$startDateTime'" .
            " AND produced_dt <'$endDateTime'" .
            " AND product_id='$product_id'" .
            " GROUP BY product_id";

        // Log::info($strSQL);

        $weights = DB::select($strSQL);
        $weight_in = 0;
        foreach ($weights as $weight) {
            $weight_in += $weight->weight_in;
        }

        return $weight_in;
    }

    /*製品出庫実績を取得する */
    public static function GetWeightOut($product_id, $cur_date)
    {
        $strSQL = 'SELECT COALESCE(sum(contract_weight),0) weight_out' .
            '   FROM greenearth.v_contract_pellet ' .
            " WHERE  product_id = '$product_id' AND contract_date='$cur_date' " ;
  
        $weight = DB::select($strSQL);

        $weight_out = 0;
        if (count($weight) > 0) {
            $weight_out = $weight[0]->weight_out;
        }
        return $weight_out;
    }

    /** 製品日次入出庫情報を更新する*/
    public static function doUpdate($date)
    {
        Log::info("ペレット入出庫処理開始...");
        DB::statement('truncate table greenearth.t_daily_product');
        DB::statement('ALTER TABLE greenearth.t_daily_product AUTO_INCREMENT = 1 ');

        $Products = [];

        $now = Carbon::now()->format('Y-m-d H:i:s');
        DB::table("m_product")
            ->orderBy("product_id")->chunk(
                100,
                function ($products) use (&$Products, $date, $now) {
                    foreach ($products as $p) {

                        $product_id = $p->product_id;
                        $prevDate = Carbon::createFromFormat('Y-m-d H:i',$date.' 00:00');

                        while ($prevDate <= $now) {
                            $prevDate_str = $prevDate->format('Y-m-d');
                            $Products[] = [
                                'product_id' => $product_id,
                                'target_date' => $prevDate_str,
                                'weight_in' => self::GetWeightIn($product_id, $prevDate),
                                'weight_out' => self::GetWeightOut($product_id, $prevDate_str),
                                'created_by' => 'ProductDailyAggregate',
                                'created_at' => $now,
                                'updated_by' => 'ProductDailyAggregate',
                                'updated_at' => $now,
                            ];

                            $prevDate->addDay();
                        }
                    }
                }
            );

        DailyProduct::insert($Products);

        Log::info("ペレット入出庫処理終了...");
    }
}
