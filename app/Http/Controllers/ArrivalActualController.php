<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\ArrivalActual;
use App\Models\ArrivalActualDetail;
// use App\Models\ViewArrivalDetail;
use App\Models\ViewArrivalAll;
use App\Models\StockCrushed;
use Illuminate\Support\Carbon;
use App\Http\Requests\Api\ArrivalActualRequest;
use App\Http\Requests\Api\ArrivalActualDetailRequest;
use App\Services\DailyMaterialService;
use Illuminate\Support\Facades\Log;
use App\Models\Setting;

use function PHPUnit\Framework\empty;

class ArrivalActualController extends Controller
{
    protected $dailyMaterialService;
    protected $dailyCrushedService;

    public function __construct(DailyMaterialService $dailyMaterialService)
    {
        $this->dailyMaterialService = $dailyMaterialService;
    }

    public function All(Request $request)
    {
        // $arrivalactuals = ArrivalActual::with('customer')->orderby('actual_date', 'desc')->get();
        // $my_query = ArrivalActual::query();
        $my_query = ViewArrivalAll::query();

        if ($request['material_id'] != null) {
            $material_id = $request['material_id'];
            $my_query->where('material_id', '=', $material_id);
        }

        if ($request['crushing_status'] != null) {
            //粉砕状態により検索
            $status = $request['crushing_status'];
            $my_query->where('crushing_status', '=', $status);
        }

        if ($request['customer_id'] != null) {
            $customer_id = $request['customer_id'];
            $my_query->where('customer_id', '=', $customer_id);
        }

        if ($request['startDate'] != null) {
            $date = Setting::instance()->getStartDateTimeOfBusinessDate($request['startDate']);
            $my_query->where('actual_date', '>=', $date);
        }
        if ($request['endDate'] != null) {
            $date = Setting::instance()
                ->getStartDateTimeOfBusinessDate($request['endDate'])
                ->addDay();
            $my_query->where('actual_date', '<', $date);
        }

        $arrivalactuals = $my_query->orderby('arrival_id', 'desc')->get();
        return $this->success($arrivalactuals);
    }

    public function AllDetails(Request $request)
    {
        // $arrivalactuals = ArrivalActual::with('customer')->orderby('actual_date', 'desc')->get();

        $strSQL =
            'SELECT ROW_NUMBER() OVER (PARTITION BY d.arrival_id) AS rowno' .
            ' , d.arrival_id, d.arrival_date, d.material_id, d.arrival_weight, d.crushing_status, d.aad_id, d.note ' .
            ' , d.customer_id, d.blended, d.destination, m.material_name FROM greenearth.t_arrival_details d' .
            ' inner join m_material m on d.material_id = m.material_id ';

        $strWhere = '';

        if ($request['material_id'] != null) {
            $material_id = $request['material_id'];

            $strWhere = " WHERE d.material_id ='$material_id'";
        }

        if ($request['crushing_status'] != null) {
            $status = $request['crushing_status'];

            if (empty($strWhere)) {
                $strWhere = " WHERE d.crushing_status =$status";
            } else {
                $strWhere .= " AND d.crushing_status =$status";
            }
        }

        //2022.11.30
        if ($request['blended'] != null) {
            $blended = $request['blended'];

            if (empty($strWhere)) {
                $strWhere = " WHERE d.blended = $blended";
            } else {
                $strWhere .= " AND d.blended = $blended";
            }
        }

        if ($request['customer_id'] != null) {
            $customer_id = $request['customer_id'];
            if (empty($strWhere)) {
                $strWhere = " WHERE d.customer_id = '$customer_id'";
            } else {
                $strWhere .= " AND d.customer_id = '$customer_id'";
            }
        }

        if (strcmp($request['wuf'], 'mobile') == 0) {
            // スマートフォンからの問い合わせのため、当日とする
            $now = Carbon::now('Asia/Tokyo');
            $from = Setting::instance()->getBusinessDate($now);
            $to = Carbon::instance($from)->addDay();

            if (empty($strWhere)) {
                $strWhere = " WHERE d.arrival_date >= '$from' AND d.arrival_date < '$to' ";
            } else {
                $strWhere .= " AND d.arrival_date >= '$from' AND d.arrival_date < '$to' ";
            }
        } else {
            // PC側から
            if ($request['startDate'] != null) {
                $date = Setting::instance()->getStartDateTimeOfBusinessDate($request['startDate']);

                if (empty($strWhere)) {
                    $strWhere = " WHERE d.arrival_date >= '$date' ";
                } else {
                    $strWhere .= " AND d.arrival_date >= '$date' ";
                }
            }
            if ($request['endDate'] != null) {
                $date = Setting::instance()
                    ->getStartDateTimeOfBusinessDate($request['endDate'])
                    ->addDay();

                if (empty($strWhere)) {
                    $strWhere = " WHERE d.arrival_date < '$date' ";
                } else {
                    $strWhere .= " AND d.arrival_date < '$date' ";
                }
            }
        }

        $strSQL = $strSQL . $strWhere . ' ORDER BY aad_id desc ';
        // $my_query = ViewArrivalDetail::query();

        // if ($request['material_id'] != null) {

        //     $material_id = $request['material_id'];
        //     $my_query->where('material_id', '=', $material_id);
        // }

        // if ($request['crushing_status'] != null) {
        //     //粉砕状態により検索
        //     $status = $request['crushing_status'];
        //     $my_query->where('crushing_status', '=', $status);
        // }

        // if ($request['customer_id'] != null) {

        //     $customer_id = $request['customer_id'];
        //     $my_query->where('customer_id', '=', $customer_id);
        // }

        //

        // $arrivaldetails = $my_query->orderby('aad_id', 'desc')->get();

        $arrivaldetails = DB::select($strSQL);
        return $this->success($arrivaldetails);
    }

    public function Add(ArrivalActualRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $arrivalactual = [
                // 'customer_id' => $request->customer_id,
                'actual_date' => $request->actual_date,
                'actual_ampm' => $request->actual_ampm,
                'arrival_note' => $request->arrival_note,
                'customer_id' => $request->customer_id,
            ];

            $act = ArrivalActual::create($arrivalactual);
            $arrival_id = $act->arrival_id;

            $aads = array_map(function ($item) use ($request, $arrival_id) {
                $customer_id = $request->customer_id;
                $material_id = $item['material_id'];
                $crushing_status = $item['crushing_status'];
                $target_date = $request->actual_date;
                $note = '';
                if (isset($item['note'])) {
                    $note = $item['note'];
                }
                $arrivalactualdetail = [
                    'arrival_id' => $arrival_id,
                    'arrival_date' => $request->actual_date,
                    'arrival_weight' => str_replace(',', '', $item['arrival_weight']),
                    'crushing_status' => $crushing_status,
                    'material_id' => $material_id,
                    'customer_id' => $customer_id,
                    'note' => $note,
                    'blended' => 0, //2023.03.20
                ];

                $aad = ArrivalActualDetail::create($arrivalactualdetail);
                $crushing_status = $aad->crushing_status;

                if ($crushing_status === 1) {
                    $aad_id = $aad->aad_id;
                    $material_id = $aad->material_id;
                    $arrival_weight = $aad->arrival_weight;

                    $stockcrushed = [
                        'material_id' => $material_id,
                        'processed' => 0,
                        'stocked_dt' => $arrivalactualdetail['arrival_date'],
                        'crushed_weight' => $arrival_weight,
                        'original_weight' => $arrival_weight,
                        'aad_id' => $aad_id,
                    ];
                    StockCrushed::create($stockcrushed);
                }

                $this->dailyMaterialService->updateDailyInByAddDelete($material_id, $target_date, $crushing_status);
            }, $request->details);

            return $this->success('add success');
        });
    }

    public function Detail(ArrivalActualRequest $request, $arrival_id)
    {
        $actual = ArrivalActual::with('customer')->find($arrival_id);
        if (!$actual) {
            $message = 'arrival_id :$arrival_id が見つかりませんでした。';
            Log::warning($message);
            return $this->notFound([$message]);
        }

        $details = ArrivalActualDetail::with('material')
            ->where('arrival_id', $arrival_id)
            ->get()
            ->toArray();
        $actual['details'] = $details;

        return $this->success($actual);
    }

    public function ArrivalActuals(Request $request)
    {
        $arrivalactuals = ArrivalActual::orderby('created_at', 'desc')
            ->with('customer')
            ->get();
        return $this->success($arrivalactuals);
    }

    public function ArrivalActualDetail(Request $request, $aad_id)
    {
        // $arrivalactuals = ArrivalActualDetail::where('aad_id', $aad_id)->orderby('aad_id', 'desc')->with('material')->get();

        // return $this->success($arrivalactuals);

        $arrivalDetail = ArrivalActualDetail::with('material')->find($aad_id);

        return $this->success($arrivalDetail);
    }

    public function OrderByDetail(Request $request, $arrival_id)
    {
        $arrivalactuals = ArrivalActualDetail::where('arrival_id', $arrival_id)
            ->orderby('material_id', 'asc')
            ->orderby('crushing_status', 'asc')
            ->orderby('arrival_weight', 'desc')
            ->with('material')
            ->get();

        return $this->success($arrivalactuals);
    }

    //mobile側　入荷実績（親）だけ入力時の処理
    public function AddArrivalActual(ArrivalActualRequest $request)
    {
        $arrivalactual = $request->all();
        if (!$request->actual_date) {
            $arrivalactual['actual_date'] = Carbon::now();
        }
        $act = ArrivalActual::create($arrivalactual);

        return $this->setStatusCode(204)->success('$act->arrival_id');
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
            $arrivalDetail['blended'] = 0; //2023.03.20 add by yin
            $aad = ArrivalActualDetail::create($arrivalDetail);

            $material_id = $aad->material_id;
            $crushing_status = $aad->crushing_status;
            if ($crushing_status === 1) {
                $stockcrushed = [
                    'material_id' => $material_id,
                    'processed' => 0,
                    'stocked_dt' => $target_date,
                    'crushed_weight' => $aad->arrival_weight,
                    'original_weight' => $aad->arrival_weight,
                    'aad_id' => $aad->aad_id,
                ];
                StockCrushed::create($stockcrushed);
            }

            $this->dailyMaterialService->updateDailyInByAddDelete($material_id, $target_date, $crushing_status);
        });
    }

    //pc側、arrival_actualを編集 廃止予定
    public function UpdateArrivalActual(ArrivalActualRequest $request, $arrival_id)
    {
        $actual = ArrivalActual::find($arrival_id);
        if (!$actual) {
            $message = 'arrival_id :' . $arrival_id . ' 変更に失敗しました。';
            Log::warning($message);
            return $this->notFound([$message]);
        }

        $arrivalactual = $request->all();
        $actual->Update($arrivalactual);
    }

    //mobile/pc 1つのarrival_detailを修正する
    public function UpdateArrivalDetail(ArrivalActualDetailRequest $request, $aad_id)
    {
        $aad = ArrivalActualDetail::find($aad_id);
        if (!$aad) {
            $message = 'aad_id :' . $aad_id . ' 変更に失敗しました。';
            Log::warning($message);
            return $this->notFound(['$aad_id の変更に失敗しました。']);
        }

        return DB::transaction(function () use ($request, $aad, $aad_id) {
            $aadNew = $request->all();

            $target_date = $request['arrival_date'];

            $material_id = $request['material_id'];

            $stockCrushed = StockCrushed::where('aad_id', $aad_id)->first();
            if ($stockCrushed) {
                //update前は粉砕済みである
                if ($stockCrushed->processed == 1) {
                    //粉砕済みで入荷、しかもブレンダーに投入した場合、変更不可
                    $message = 'aad_id :' . $aad_id . '既に使用されたため、変更不可です。';
                    Log::warning($message);
                    return $this->failed([$message], 422);
                }

                if ($aadNew['crushing_status'] == 0) {
                    //粉砕済⇒未粉砕、t_stock_crushedから当該レコードを削除
                    $stockCrushed->delete();
                } else {
                    $stockCrushed->update([
                        'material_id' => $material_id,
                        'crushed_weight' => $request->arrival_weight,
                        'original_weight' => $request->arrival_weight,
                    ]);
                }
            } else {
                //未粉砕⇒粉砕、t_stock_crushedに当該レコードを挿入
                if ($aadNew['crushing_status'] == 1) {
                    $stockcrushed = [
                        'aad_id' => $aad_id,
                        'material_id' => $material_id,
                        'crushed_weight' => $request->arrival_weight,
                        'original_weight' => $request->arrival_weight,
                        'stocked_dt' => $target_date,
                    ];

                    StockCrushed::create($stockcrushed);
                }
            }

            //在庫情報から変更前のデータを削除
            $f_materialId = $aad->material_id;
            $f_crushingStatus = $aad->crushing_status;
            $f_arrivalDate = $aad->arrival_date;
            $aad->Update($aadNew);
            $this->dailyMaterialService->updateDailyInByAddDelete($f_materialId, $f_arrivalDate, $f_crushingStatus);
            $this->dailyMaterialService->updateDailyInByAddDelete(
                $aadNew['material_id'],
                $target_date,
                $aadNew['crushing_status']
            );

            return $this->success('update success');
        });
    }

    //arrival_detail 1つ削除
    public function ArrivalDetailDelete(ArrivalActualDetailRequest $request, $aad_id)
    {
        $arrivalDetail = ArrivalActualDetail::find($aad_id);
        if (!$arrivalDetail) {
            $message = "aad_id: $aad_id が既に削除された。";
            Log::warning($message);
            return $this->setStatusCode(204)->success('no content');
        }

        return DB::transaction(function () use ($request, $arrivalDetail, $aad_id) {
            $target_date = $arrivalDetail->arrival_date;
            $material_id = $arrivalDetail->material_id;
            $crushing_status = $arrivalDetail->crushing_status;

            if ($arrivalDetail->blended == 1) {
                $message = '既に使用されたため、削除できません。';
                Log::warning($message);
                return $this->failed([$message], 422);
            }
            // $stockCrushed = StockCrushed::where('aad_id', $aad_id)->first();
            // if ($stockCrushed) {
            //     if ($stockCrushed->processed == 0) {
            //         $stockCrushed->delete();
            //     } else {
            //         $message = "aad_id: $aad_id が既に使用されたため、削除できません。";
            //         Log::warning($message);
            //         return $this->failed([$message], 422);
            //     }
            // }

            $arrivalDetail->delete();

            $this->dailyMaterialService->updateDailyInByAddDelete($material_id, $target_date, $crushing_status);

            return $this->setStatusCode(204)->success('no content');
        });
    }

    //PC側　一括削除
    public function Delete(ArrivalActualRequest $request, $arrival_id)
    {
        // $arrival = ArrivalActual::find($arrival_id);
        // if (!$arrival) {

        //     //当該契約存在しない（削除済み）場合、削除成功を返す
        //     $message = "arrival_id: $arrival_id が既に削除された。";
        //     Log::warning($message);
        //     return $this->setStatusCode(204)->success('no content');
        // }

        // //使用済をチェック、存在している場合、一括削除不可
        // $strSQL = " SELECT id,product_id FROM t_stock_crushed  " .
        //     " WHERE aad_id IN  ( SELECT aad_id FROM greenearth.t_arrival_details" .
        //     "    WHERE arrival_id = $arrival_id )" .
        //     " AND processed = 1 ";

        // $processed = DB::select($strSQL);
        // if (count($processed)) {
        //     $message = "arrival_id: $arrival_id がの一部が既に使用されたため、一括削除ができません。";
        //     Log::warning($message);
        //     return $this->failed([$message], 422);
        // }

        // $target_date = "";
        // return DB::transaction(function () use ($request, $arrival, &$target_date) {
        //     $target_date = $arrival->actual_date;
        //     $arrival_id = $arrival->arrival_id;
        //     $details = ArrivalActualDetail::where('arrival_id', $arrival_id)->get();
        //     foreach ($details as $v) {
        //         $aad_id = $v['aad_id'];
        //         $stockCrushed = StockCrushed::where('aad_id', $aad_id)->first();
        //         if ($stockCrushed) {
        //             if ($stockCrushed->processed == 0) {
        //                 $stockCrushed->delete();
        //             } else {
        //                 $message = "arrival_id: $arrival_id がの一部が既に使用されたため、一括削除ができません。";
        //                 Log::warning($message);
        //                 return $this->failed([$message], 422);
        //             }
        //         } else {
        //             $v->delete();
        //         }
        //     }
        //     $arrival->delete();
        //     // $this->dailyMaterialService->updateDailyIn($target_date);
        //     return $this->success('delete success');
        // });
    }

    // 2022.04.23 入荷実績当日集計
    public function DaySum(Request $request)
    {
        $now = Carbon::now('Asia/Tokyo');
        $from = Setting::instance()->getBusinessDate($now);

        $to = Carbon::instance($from)->addDay();
        $totals = ArrivalActualDetail::orderby('material_id', 'asc')
            ->where('arrival_date', '>=', $from)
            ->where('arrival_date', '<', $to)
            ->groupBy('material_id', 'crushing_status')
            ->select(
                'material_id',
                'crushing_status',
                ArrivalActualDetail::raw('sum(arrival_weight) as arrival_weight')
            )
            ->get();

        return $this->success($totals);
    }
}
