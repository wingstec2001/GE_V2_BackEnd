<?php

namespace App\business;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use App\model\MedalAccessType;
use Illuminate\Support\Facades\Log;
use Exception;

use App\Models\Setting;

class GetsujiAggregate
{

    private static $getsujiDates;
    private static $defaultStartDate;

    public static function InitLastStatDates()
    {
        self::$getsujiDates = [];
        $getsujiDates = DB::select("SELECT product_id, max(yyyymme) AS stat_date FROM t_getusji_info GROUP BY product_id");
        foreach ($getsujiDates as $cd) {
            self::$getsujiDates[$cd->product_id] = new Carbon($cd->stat_date);
        }

        self::$defaultStartDate = Carbon::createFromFormat('Y-m-d His', Setting::instance()->getNichijiStartDate() . ' 000000');
    }

    /** 指定顧客毎日統計データ最後統計日を取得 */
    public static function GetGetsujiLastDate($productId)
    {
        $ret = self::$getsujiDates[$productId] ?? self::$defaultStartDate;
        // log::info($ret);
        return $ret;
    }


    /** 材料入庫取得by product_id, date */
    public static function GetWeightIn($product_id, $cur_date)
    {
        $enddate =  Carbon::instance($cur_date)->addDay();
        $startDateTime = Setting::instance()->getStartDateTimeOfBusinessDate($cur_date);
        $endDateTime = Setting::instance()->getStartDateTimeOfBusinessDate($enddate);

        $strSQL = 'SELECT sum(arrival_weight) weight_in ' .
            ' FROM greenearth.t_arrival_details ' .
            " WHERE crushing_status = 0 " .
            " AND arrival_date>='" . $startDateTime . "'" .
            " AND arrival_date <'" . $endDateTime . "'" .
            " AND product_id='" . $product_id . "'" .
            " GROUP BY product_id";

        // log::info($strSQL);

        $weights = DB::select($strSQL);
        $weight_in = 0;
        foreach ($weights as $weight) {
            $weight_in += $weight->weight_in;
        }

        return $weight_in;
    }

    public static function GetWeightOut($product_id, $cur_date)
    {
        $enddate =  Carbon::instance($cur_date)->addDay();
        $startDateTime = Setting::instance()->getStartDateTimeOfBusinessDate($cur_date);
        $endDateTime = Setting::instance()->getStartDateTimeOfBusinessDate($enddate);

        $strSQL = 'SELECT sum(actual_weight) weight_out ' .
            ' FROM greenearth.t_crushing_actual ' .
            " WHERE actual_date>='" . $startDateTime . "'" .
            " AND  actual_date <'" . $endDateTime . "'" .
            " AND product_id='" . $product_id . "'" .
            " GROUP BY product_id";

        $weight = DB::select($strSQL);

        $weight_out = 0;
        if (count($weight) > 0) {
            $weight_out = $weight[0]->weight_out;
        }
        return $weight_out;
    }
    /** 顧客毎日来店情報を更新する*/
    public static function doUpdate()
    {
        Log::info('材料入出庫日次情報更新開始');
        self::InitLastStatDates();

        $materials = [];
        DB::table("m_product")
            ->whereNull('deleted_at')
            ->orderBy("product_id")->chunk(
                100,
                function ($products) use (&$materials) {
                    foreach ($products as $p) {
                        $product_id = $p->product_id;

                        // 1. 最後統計日を取得
                        $lastStatDate = self::GetGetsujiLastDate($product_id);

                        // 2. 最後統計日が過去日の場合翌日から毎日情報を統計する, 今日の来店情報は常に更新する。来店されてない日のレコードを作成しない
                        $today = Carbon::today();
                        $prevDate = Carbon::instance($lastStatDate);
                        $yyyymm = $prevDate->format('Ym');
                        
                        // 既存データをまず削除する
                        DB::table('t_getsuji_info')->where([
                            ['product_id', "=", $product_id],
                            ['yyyymm', ">=", $prevDate]
                        ])->delete();

                        while ($prevDate < $today) {
                            $prevDate_str = $prevDate->format('Y-m-d');
                            $getsujis[] = [
                                'product_id' => $product_id,
                                'yyyymm' => $prevDate_str,
                                'weight_in' => self::GetWeightIn($product_id, $prevDate),
                                'weight_out' => self::GetWeightOut($product_id, $prevDate)
                            ];
                            // log::info('Count:' . count($materials) . ', product_id:' . $product_id);
                            $prevDate->addMonth();
                        }
                    }
                }
            );


        $now = Carbon::now()->format('Y-m-d H:i:s');

        try {
            DB::beginTransaction();
            foreach ($materials as $material) {
                DB::table('t_daily_material')->insert([
                    'product_id' => $material['product_id'],
                    'target_date' => $material['target_date'],
                    'weight_in' => $material['weight_in'],
                    'weight_out' => $material['weight_out'],
                    'created_by' => 'MaterialDailyAggregate',
                    'created_at' => $now,
                    'updated_by' => 'MaterialDailyAggregate',
                    'updated_at' => $now,
                ]);
            }
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            Log::error('DBにエラーが発生しました' . $e->getMessage());
        }

        Log::info('材料入出庫日次情報更新終了。');
    }
}
