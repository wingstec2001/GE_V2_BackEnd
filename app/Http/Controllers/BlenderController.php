<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Blender;
use App\Models\ViewBlender;
use App\Models\StockCrushed;
use App\Models\CrushingActual;
use App\Models\ArrivalActualDetail;
use App\Http\Requests\Api\BlenderRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Setting;
use App\Services\DailyCrushedService;
use Illuminate\Support\Carbon;

class BlenderController extends Controller
{

    protected $dailyCrushedService;
    public function __construct(DailyCrushedService $dailyCrushedService)
    {
        $this->dailyCrushedService = $dailyCrushedService;
    }
    public function All(Request $request)
    {
        $my_query = ViewBlender::query();

        if ($request['start_date'] != null) {
            $date = Setting::instance()->getStartDateTimeOfBusinessDate($request['start_date']);
            $my_query->where('blended_dt', '>=', $date);
        }

        if ($request['end_date'] != null) {
            $date = Setting::instance()->getStartDateTimeOfBusinessDate($request['end_date'])->addDay();
            $my_query->where('blended_dt', '<', $date);
        }

        if ($request['material_id'] != null) {
            $my_query->where('material_id', $request['material_id']);
        }

        if ($request['origin'] != null) {
            $my_query->where('origin', $request['origin']);
        }


        if ($request['sort'] != null) {
            $params = explode("-", $request['sort']);
            $my_query->orderby($params[0], $params[1]);
        } else {
            $my_query->orderby('id', 'desc');
        }

        $result = $my_query->get();

        return $this->success($result);
    }

    public function Add(BlenderRequest $request)
    {
        return DB::transaction(function () use ($request) {

            $sc_id = $request->stock_crushed_id;
            $sc = StockCrushed::where('id', $sc_id)->first();
            if (!$sc) {
                $message = "stockcrushed_id: $sc_id は無効です。";
                Log::warning($message);
                return $this->notFound([$message]);
            }

            if ($sc['processed'] === 1) {
                $message = "stockcrushed_id: $sc_id はすでに処理済です。";
                Log::warning($message);
                return $this->setStatusCode(204)->success('no content');
            }

            $blender = [
                'blended_dt' => $request->blended_dt,
                'blended_weight' => $sc->crushed_weight,
                'material_id' => $sc->material_id,
                'stock_crushed_id' => $request->stock_crushed_id,
            ];

            $ret = Blender::create($blender);


            //粉砕済み在庫を変更
            $ret = $sc->Update(['processed' => 1, 'destination' => 1, 'crushed_weight' => 0]);

            //入荷済みから減らす場合
            $aad_id = $sc->aad_id;
            if (isset($aad_id)) {
                $aad  = ArrivalActualDetail::find($aad_id);
                if (isset($aad)) {
                    if ($aad->blended == 0) {
                        $aad->Update(['blended' => 1]);
                    }
                }
            }

            $crushed_id = $sc->crushed_id;
            if (isset($crushed_id)) {
                $crushed = CrushingActual::find($crushed_id);
                if (isset($crushed)) {
                    $crushed->Update(['blended' => 1]);
                }
            }


            $this->dailyCrushedService->updateWeightOut($sc->material_id, $request->blended_dt, 1);

            return $this->success('create success');
        });
    }

    public function Detail(BlenderRequest $request, $id)
    {
        $blender = Blender::with('material')->find($id);
        if (!$blender) {
            $message = "id: $id が見つかりませんでした。";
            Log::warning($message);
            return $this->notFound([$message]);
        }

        return $this->success($blender);
    }



    public function Delete(BlenderRequest $request, $id)
    {
        $blender = Blender::find($id);
        if (!$blender) {
            $message = "blender_id: $id が既に削除された。";
            Log::warning($message);
            return $this->setStatusCode(204)->success('no content');
        }

        return DB::transaction(function () use ($blender) {
            $sc_id = $blender->stock_crushed_id;
            $blended_dt = $blender->blended_dt;
            $blended_weight = $blender->blended_weight;
            $blender->delete();

            $sc = StockCrushed::where('id', $sc_id)->first();
            if (!$sc) {
                $message = "stockcrushed_id: $sc_id が既に削除された。";
                Log::warning($message);
            } else {
                $this->undoStockCrushed($sc, $blended_weight);
                $this->dailyCrushedService->updateWeightOut($sc->material_id, $blended_dt, 1);
            }

            return $this->setStatusCode(204)->success('no content');
        });
    }

    private function undoStockCrushed(StockCrushed $sc, $blended_weight)
    {
        //t_stock_crushedのcrushed_weightを戻す
        $crushed_weight = $sc->crushed_weight + $blended_weight;

        $aad_id = $sc->aad_id;
        $crushed_id = $sc->crushed_id;
        $original_weight = $sc->original_weight;

        $destination = $sc->destination;

        //紛済み入庫の場合
        if (isset($aad_id)) {
            $aad  = ArrivalActualDetail::find($aad_id);
            if ($aad == null) {
                log::error('can not find from arrival_details, aad_id:$aad_id');
                return false;
            }


            if ($crushed_weight == $original_weight) {
                $processed = 0;
                $destination = 0;
                //入荷済のblendedをfalseにする
                $aad->update([
                    'blended' => 0,
                ]);
            }
        }

        //自社紛入庫の場合
        if (isset($crushed_id)) {
            $ca = CrushingActual::find($crushed_id);
            if ($ca == null) {
                log::error('can not find from crushing_actual, crushing_id:$crushed_id');
                return false;
            }

            if ($crushed_weight == $original_weight) {

                $destination = 0;
                //自社紛済のblendedをfalseにする
                $ca->update([
                    'blended' => 0,
                ]);
            }
        }

        $sc->update([
            'crushed_weight' => $crushed_weight,
            'processed' => 0,
            'destination' => $destination,
        ]);

        return true;
    }

    public function DaySum(Request $request)
    {

        $now = Carbon::now('Asia/Tokyo');
        $from = Setting::instance()->getBusinessDate($now);
        $to =  Carbon::instance($from)->addDay();

        $totals = Blender::orderby('material_id', 'asc')
            ->where('blended_dt', '>=', $from)
            ->where('blended_dt', '<', $to)
            ->groupBy('material_id')
            ->select('material_id',  Blender::raw("sum(blended_weight) as blended_weight"))
            ->get();

        return $this->success($totals);
    }


    //入荷時粉砕済みから、重量によるブレンド追加
    public function AddByWeight(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $material_id = $request->material_id;
            $weight = $request->blended_weight;

            $sc = StockCrushed::where('processed', 0)
                ->where('material_id', $material_id)
                // ->where('crushed_weight','>=',$weight)   2022.12.12 一番近い値から最大値に変更
                ->orderby('crushed_weight', 'desc')
                ->first();

            if (!$sc) {
                $message = '操作に失敗しました。再度行って下さい。[$material_id:$weight]';
                Log::warning($message);
                return $this->notFound([$message]);
            }

            //ブレンド実績を作成
            if ($request->blended_dt != null) {
                $blended_dt = $request->blended_dt;
            } else {
                $blended_dt = Carbon::now('Asia/Tokyo');
            }

            $blender = [
                'blended_dt' => $blended_dt,
                'blended_weight' => $weight,
                'material_id' => $material_id,
                'stock_crushed_id' => $sc->id,
            ];
            $ret = Blender::create($blender);

            // //粉砕済みの在庫重量を減らす
            $diff = $sc->crushed_weight - $weight;
            $processed = false;
            if ($diff == 0)  $processed = true;

            $sc->Update([
                'crushed_weight' => $diff,
                'processed' => $processed
            ]);

            //入荷済みから減らす場合
            $aad_id = $sc->aad_id;
            if (isset($aad_id)) {
                $aad  = ArrivalActualDetail::find($aad_id);
                if ($aad != null) {
                    if ($aad->blended == 0) {
                        $aad->Update(['blended' => 1]);
                    }
                } else {
                    log::error('can not find aad_id: $aad_id');
                }
            }

            $crushed_id = $sc->crushed_id;
            if (isset($crushed_id)) {
                $crushed = CrushingActual::find($crushed_id);
                $crushed->Update(['blended' => 1]);
            }

            //紛済み在庫を減らす
            $this->dailyCrushedService->updateWeightOut($material_id, $blended_dt, 1);
            return $this->success('create success');
        });
    }
}
