<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\ArrivalPellet;
use App\Models\StockCrushed;
use Illuminate\Support\Carbon;
use App\Http\Requests\Api\ArrivalPelletRequest;

use App\Services\DailyProductService;
use Illuminate\Support\Facades\Log;
use App\Models\Setting;

use function PHPUnit\Framework\empty;

class ArrivalPelletController extends Controller
{
    protected $dailyProductService;

    public function __construct(DailyProductService $dailyProductService)
    {
        $this->dailyProductService = $dailyProductService;
    }

    public function All(Request $request)
    {
        // $arrivalactuals = ArrivalActual::with('customer')->orderby('actual_date', 'desc')->get();
        // $my_query = ArrivalActual::query();
        $my_query = ArrivalPellet::query();

        if ($request['product_id'] != null) {
            $product_id = $request['product_id'];
            $my_query->where('product_id', '=', $product_id);
        }

        if ($request['customer_id'] != null) {
            $customer_id = $request['customer_id'];
            $my_query->where('customer_id', '=', $customer_id);
        }

        if ($request['startDate'] != null) {
            $date = Setting::instance()->getStartDateTimeOfBusinessDate($request['startDate']);
            $my_query->where('arrival_date', '>=', $date);
        }
        if ($request['endDate'] != null) {
            $date = Setting::instance()
                ->getStartDateTimeOfBusinessDate($request['endDate'])
                ->addDay();
            $my_query->where('arrival_date', '<', $date);
        }

        $arrivalpellets = $my_query->orderby('aad_id', 'desc')->get();
        return $this->success($arrivalpellets);
    }

    public function Add(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $aads = array_map(function ($item) use ($request) {
                $customer_id = $request->customer_id;
                $product_id = $item['product_id'];

                $target_date = $request->actual_date;

                $note = '';
                if (isset($item['note'])) {
                    $note = $item['note'];
                }

                $arrivalactualdetail = [
                    'arrival_date' => $request->actual_date,
                    'arrival_weight' => str_replace(',', '', $item['arrival_weight']),

                    'product_id' => $product_id,
                    'customer_id' => $customer_id,
                    'note' => $note,
                ];

                $aad = ArrivalPellet::create($arrivalactualdetail);

                $this->dailyProductService->updateDailyInByAddDelete($product_id, $target_date);
            }, $request->details);

            return $this->success('add success');
        });
    }

    public function Detail(Request $request, $aad_id)
    {
        $actual = ArrivalPellet::find($aad_id);
        if (!$actual) {
            $message = 'arrival_id :$arrival_id が見つかりませんでした。';
            Log::warning($message);
            return $this->notFound([$message]);
        }

        return $this->success($actual);
    }

    public function ArrivalPellet(Request $request, $aad_id)
    {
        $arrivalDetail = ArrivalPellet::find($aad_id);

        return $this->success($arrivalDetail);
    }

    /// mobile側　arrival_detail １つを追加
    public function AddArrivalDetail(ArrivalActualDetailRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $arrivalDetail = $request->all();
            if (!$request->arrival_date) {
                // mobileからの場合
                $arrivalDetail['arrival_date'] = Carbon::now('Asia/Tokyo');
            }

            $target_date = $arrivalDetail['arrival_date'];
            $aad = ArrivalActualDetail::create($arrivalDetail);

            $product_id = $aad->product_id;
            $crushing_status = $aad->crushing_status;
            if ($crushing_status === 1) {
                $stockcrushed = [
                    'product_id' => $product_id,
                    'processed' => 0,
                    'stocked_dt' => $target_date,
                    'crushed_weight' => $aad->arrival_weight,
                    'aad_id' => $aad->aad_id,
                ];
                StockCrushed::create($stockcrushed);
            }

            // $this->dailyMaterialService->updateDailyInByAddDelete($product_id, $target_date, $crushing_status);
        });
    }

    //mobile/pc 1つのarrival_detailを修正する
    public function Update(ArrivalPelletRequest $request, $aad_id)
    {
        $aad = ArrivalPellet::find($aad_id);
        if (!$aad) {
            $message = 'aad_id :' . $aad_id . ' 変更に失敗しました。';
            Log::warning($message);
            return $this->notFound(['$aad_id の変更に失敗しました。']);
        }

        return DB::transaction(function () use ($request, $aad, $aad_id) {
            $aadNew = $request->all();

            $target_date = $request['arrival_date'];
            $product_id = $request['product_id'];

            $ret = $aad->Update($aadNew);

            $this->dailyProductService->updateDailyInByAddDelete($product_id, $target_date);
            return $this->success('update success');
        });
    }

    //削除
    public function Delete(ArrivalPelletRequest $request, $aad_id)
    {
        $arrivalPellet = ArrivalPellet::find($aad_id);
        if (!$arrivalPellet) {
            $message = "aad_id: $aad_id が既に削除された。";
            Log::warning($message);
            return $this->setStatusCode(204)->success('no content');
        }

        return DB::transaction(function () use ($request, $arrivalPellet, $aad_id) {
            $ret = $arrivalPellet->delete();
            $target_date = $arrivalPellet['arrival_date'];

            $product_id = $arrivalPellet['product_id'];

            $this->dailyProductService->updateDailyInByAddDelete($product_id, $target_date);

            return $this->setStatusCode(204)->success('no content');
        });
    }

    // 2022.04.23 入荷実績当日集計
    public function DaySum(Request $request)
    {
        $now = Carbon::now('Asia/Tokyo');
        $from = Setting::instance()->getBusinessDate($now);

        $to = Carbon::instance($from)->addDay();
        $totals = ArrivalActualDetail::orderby('product_id', 'asc')
            ->where('arrival_date', '>=', $from)
            ->where('arrival_date', '<', $to)
            ->groupBy('product_id', 'crushing_status')
            ->select('product_id', 'crushing_status', ArrivalActualDetail::raw('sum(arrival_weight) as arrival_weight'))
            ->get();

        return $this->success($totals);
    }
}
