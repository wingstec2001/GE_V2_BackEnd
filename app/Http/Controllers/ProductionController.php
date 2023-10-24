<?php

namespace App\Http\Controllers;

use App\Models\Production;
use Illuminate\Http\Request;
use App\Http\Requests\Api\ProductionRequest;
use Carbon\Carbon;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

use App\Services\DailyProductService;

class ProductionController extends Controller
{
    protected $dailyproductService;

    public function __construct(DailyProductService $dailyProductService)
    {
        $this->dailyproductService = $dailyProductService;
    }

    //一覧取得
    public function All(Request $request)
    {
        $my_query = Production::query();

        if (strcmp($request['wuf'], 'mobile') == 0) {

            // スマートフォンからの問い合わせのため、当日とする
            $now = Carbon::now('Asia/Tokyo');
            $from = Setting::instance()->getBusinessDate($now);

            $my_query->where('produced_dt', '>=', $from);

            $to =  Carbon::instance($from)->addDay();
            $my_query->where('produced_dt', '<', $to);
        } else {
            if ($request['start_date'] != null) {
                $date = Setting::instance()->getStartDateTimeOfBusinessDate($request['start_date']);
                $my_query->where('produced_dt', '>=', $date);

                $str = $date->format("Y-m-d H:i:s");
            }
            if ($request['end_date'] != null) {
                $date = Setting::instance()->getStartDateTimeOfBusinessDate($request['end_date'])->addDay();
                // var_dump($date->format('Y-m-d H:i:s'));
                $my_query->where('produced_dt', '<', $date);
                $str = $date->format("Y-m-d H:i:s");
            }
        }

        if ($request['route_id'] != null) {
            $my_query->where('route_id', $request['route_id']);
        }

        if ($request['product_id'] != null) {
            $my_query->where('product_id', $request['product_id']);
        }
        if ($request['sort'] != null) {
            $params = explode("-", $request['sort']);
            $my_query->orderby($params[0], $params[1]);
        } else {
            $my_query->orderby('created_at', 'desc');
        }
        $result = $my_query->with('product')->get();
        return $this->success($result);
    }

    //詳細
    public function Details(Request $request)
    {
        $date = $request['date'];
        $routerId = $request['router'];

        $from = Setting::instance()->getStartDateTimeOfBusinessDate($date);
        $to = new Carbon($from);
        $to->addDays(1);
        $productions = Production::with('product')
            ->orderby('produced_dt', 'desc')
            ->whereBetween('produced_dt', [$from, $to])
            ->where('route_id', $routerId)->get();

        return $this->success($productions);
    }

    public function Sum(Request $request)
    {
        $date = $request['date'];
        $routerId = $request['router'];

        $now = Carbon::now('Asia/Tokyo');
        $from = Setting::instance()->getBusinessDate($now);

        $to = new Carbon($from);
        $to->addDays(1);

        $productions = Production::with('product')
            ->orderby('product_id', 'asc')
            ->whereBetween('produced_dt', [$from, $to])
            ->where('route_id', $routerId)
            ->groupBy('product_id')
            ->select('product_id', Production::raw("sum(produced_weight) as produced_weight"))
            ->get();

        return $this->success($productions);
    }

    public function Add(ProductionRequest $request)
    {
        $production = $request->all();
        if (!$request->produced_dt) {
            $production['produced_dt'] = Carbon::now('Asia/Tokyo');
        }

        return DB::transaction(function () use ($production) {
            $id = Production::create($production)->id;

            $target_date = $production['produced_dt'];
            $product_id = $production['product_id'];
            $this->dailyproductService->updateDailyInByAddDelete($product_id, $target_date);

            return $this->success(['id' => $id]);
        });
    }


    public function Detail(Request $request, $id)
    {
        $find =  Production::with('product')->find($id);
        if (!$find) {
            $message = "production_id: $id が無効です。";
            Log::warning($message);
            return $this->failed([$message], 422);
        }
        return $this->success($find);
    }

    public function Update(Request $request, $id)
    {
        $find = Production::find($id);

        if (!$find) {
            $message = "production_id: $id が無効です。";
            Log::warning($message);
            return $this->failed([$message], 422);
        }

        $production = $request->all();

        return DB::transaction(function () use ($find, $production) {
            $f_productId = $find->product_id;
            $f_producedDt = $find->produced_dt;
    
            $ret = $find->Update($production);
            if ($ret) {
                $target_date = $production['produced_dt'];
                $product_id = $production['product_id'];
                

                $this->dailyproductService->updateDailyInByAddDelete($f_productId,$f_producedDt);
                $this->dailyproductService->updateDailyInByAddDelete($product_id,$target_date);


                return $this->success('update success');
                
            } else {
                return $this->notFound(["更新に失敗しました。"]);
            }
        });
    }

    public function Delete(ProductionRequest $request, $id)
    {
        $production =  Production::find($id);
        if (!$production) {
            $message = "production_id: $id が削除された。";
            Log::warning($message);
            return $this->setStatusCode(204)->success('no content');
        }

        return DB::transaction(function () use ($production) {
            $target_date = $production['produced_dt'];
            $product_id = $production['product_id'];

            $ret = $production->delete();
            if ($ret) {
                $this->dailyproductService->updateDailyInByAddDelete($product_id, $target_date);
            }

            return $this->success('delete success');
        });
    }
}
