<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CrushingActual;
use App\Models\Setting;
use App\Models\Material;
use App\Models\StockCrushed;
use App\Http\Requests\Api\CrushingActualRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\ViewCrushedInfo;
use App\Services\DailyMaterialService;
use Exception;

class CrushingActualController extends Controller
{
    protected $dailyMaterialService;

    protected $dailyCrushedService;
    public function __construct(DailyMaterialService $dailyMaterialService)
    {
        $this->dailyMaterialService = $dailyMaterialService;
    }

    public function All(Request $request)
    {
        $my_query = ViewCrushedInfo::query();
        if ($request['start_date'] != null) {
            $date = Setting::instance()->getStartDateTimeOfBusinessDate($request['start_date']);
            $my_query->where('actual_date', '>=', $date);
        }
        if ($request['end_date'] != null) {
            $date = Setting::instance()->getStartDateTimeOfBusinessDate($request['end_date'])->addDay();
            $my_query->where('actual_date', '<', $date);
        }
        // if ($request['product_id'] != null) {
        //     $my_query->where('product_id', $request['product_id']);
        // }

        if ($request['material_id'] != null) {
            $my_query->where('material_id', $request['material_id']);
        }

        // if ($request['sort'] != null) {
        //     $params = explode("-", $request['sort']);
        //     $my_query->orderby($params[0], $params[1]);
        // } else {
        //     $my_query->orderby('actual_date', 'desc');
        // }
        $result = $my_query->orderby('crushed_id', 'desc')->get();
        return $this->success($result);
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $material_id = Material::orderBy('material_id')->pluck('material_id');
        return $this->success(compact('material_id'));
    }

    public function Details(Request $request)
    {
        $date = $request['date'];

        $from = Setting::instance()->getStartDateTimeOfBusinessDate($date);
        $to = new Carbon($from);
        $to->addDays(1);

        $crushingactuals = CrushingActual::with('material')->orderby('crushed_id', 'desc')
            ->whereBetween('actual_date', [$from, $to])->get();

        return $this->success($crushingactuals);
    }

    public function Add(CrushingActualRequest $request)
    {

        return DB::transaction(function () use ($request) {
            $crushingactual = $request->all();

            if (!$request->actual_date) {
                $crushingactual['actual_date'] = Carbon::now('Asia/Tokyo');
            }

            $material_id = $request->material_id;

            $actual_date = $crushingactual['actual_date'];

            $ca = CrushingActual::create($crushingactual);
            $sc = StockCrushed::create([
                'material_id' => $ca->material_id,
                'stocked_dt' => $ca->actual_date,
                'processed' => '0',
                'crushed_weight' => $ca->actual_weight,
                'original_weight' => $ca->actual_weight,
                'crushed_id' => $ca->crushed_id,
            ]);

            $this->dailyMaterialService->updateWeightOut($material_id, $actual_date,1);

            return $this->success(['crushed_id' => $ca->crushed_id, 'stocked_id' => $sc->id]);
        });
    }

    public function Detail(CrushingActualRequest $request, $crushed_id)
    {
        $crushingactual =  CrushingActual::with('material')->find($crushed_id);

        return $this->success($crushingactual);
    }

    public function Update(CrushingActualRequest $request, $crushed_id)
    {
        $ca = CrushingActual::with('material')->find($crushed_id);

        if (!$ca) {
            $message = "crushing_id:$crushed_id が見つかりませんでした。";
            Log::warning($message);
            return $this->notFound(['変更に失敗しました。']);
        }

        return DB::transaction(function () use ($request, $ca) {
            $crushingactual = $request->all();
            $f_materialId = $ca->material_id;
            $f_acutalDdate = $ca->actual_date;

            $acutal_date =  $crushingactual['actual_date'];

            $ca->Update($crushingactual);

            $sc = StockCrushed::where('crushed_id', $crushingactual['crushed_id'])->first();
            if (!$sc) {
                $crushed_id = $crushingactual['crushed_id'];
                $message = 'StockCrushed crushed_id :' . $crushed_id . 'が見つかりませんでした、変更不可です。';
                Log::warning($message);
                return $this->failed([$message], 422);
            }

            $sc->Update([
                'material_id' => $ca->material_id,
                'stocked_dt' => $ca->actual_date,
                'crushed_weight' => $ca->actual_weight,
                'original_weight' => $ca->actual_weight,
            ]);

            $this->dailyMaterialService->updateWeightOut($f_materialId, Carbon::parse($f_acutalDdate), 1);

            $this->dailyMaterialService->updateWeightOut($ca->material_id, Carbon::parse($acutal_date), 1);
            return $this->success(['crushed_id' => $ca->crushed_id, 'stocked_id' => $sc->id]);
        });
    }
    public function Delete(CrushingActualRequest $request, $crushed_id)
    {
        $ca = CrushingActual::find($crushed_id);

        if (!$ca) {
            $message = "crushed_id: $crushed_id が既に削除された。";
            Log::warning($message);
            return $this->setStatusCode(204)->success('no content');
        }

        return DB::transaction(function () use ($request, $ca, $crushed_id) {

            $material_id = $ca->material_id;
            $actual_date = $ca->actual_date;
            $ca->delete();
            $sc = StockCrushed::where('crushed_id', $crushed_id)->first();
            if (!$sc) {
                $message = "crushed_id: $crushed_id が既に削除された。";
                Log::warning($message);
                LOG::warning('deleted by' . Auth::User()->name, $sc->toArray());
            } else {
                $sc->delete();
                LOG::Info('deleted by' . Auth::User()->name, $ca->toArray(), $sc->toArray());
            }
           
            $this->dailyMaterialService->updateWeightOut($material_id, $actual_date,1);
            return $this->setStatusCode(204)->success('no content');
        });
    }
}
