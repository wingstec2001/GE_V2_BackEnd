<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StockCrushed;
use App\Models\Blender;
use App\Models\ArrivalActualDetail;
use App\Models\CrushingActual;
use Carbon\Carbon;
use App\Http\Requests\Api\StockCrushedRequest;
use App\Http\Resources\StockCrushedResource;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class StockCrushedController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    function __construct()
    {
        $this->middleware('permission:stockCrushed-read');
        $this->middleware('permission:stockCrushed-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:stockCrushed-update', ['only' => ['edit', 'update']]);
        $this->middleware('permission:stockCrushed-delete', ['only' => ['destroy']]);
    }
    /**
     * @OA\Get(
     *   tags={"StockCrushed"},
     *   path="/stock-crushed",
     *   summary="get all StockCrushed",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(
     *     response=200,
     *     description="OK",
     *
     *   )
     * )
     */
    public function index(Request $request)
    {

        $strSQL = 'SELECT id, material_id, crushed_weight,note,origin, aad_id, stocked_dt, crushed_id ' .
            ' FROM greenearth.v_stock_crushed_info ';

        $strWhere = '';

        if ($request['material_id'] != null) {
            $material_id = $request['material_id'];

            $strWhere = " WHERE material_id ='$material_id'";
        }

        $strSQL = $strSQL . $strWhere . ' ORDER BY material_id asc, crushed_weight desc';
        $result = DB::select($strSQL);

        $result = StockCrushedResource::collection($result);
        return $this->success($result);
    }

    //自社の粉砕済みの一覧を取得する
    public function ByInhouse(Request $request)
    {
        //自社粉砕のため,crushed_idは nullではない
        $result = StockCrushed::where('processed', 0)
            ->whereNotNull('crushed_id')
            ->orderby('material_id', 'asc')
            ->orderby('crushed_weight', 'desc')
            ->get();
        $result = StockCrushedResource::collection($result);
        return $this->success($result);
    }

    //入荷粉砕済みの一覧を取得する
    public function ByArrival(Request $request)
    {
        //自社粉砕のため,crushed_idは nullではない
        $result = StockCrushed::where('processed', 0)
            ->whereNotNull('aad_id')        //入荷ID,入荷の場合 not null
            ->orderby('material_id', 'asc')
            ->orderby('crushed_weight', 'desc')
            ->get();
        $result = StockCrushedResource::collection($result);
        return $this->success($result);
    }


    //入荷時粉砕済みから、重量によるブレンド追加
    public function AddByWeight(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $material_id = $request->material_id;
            $weight = $request->blended_weight;

            $sc = StockCrushed::where('processed', 0)
                ->where('material_id', $material_id)
                // ->where('crushed_weight','>=',$weight)    2022.12.12
                ->orderby('crushed_weight', 'desc')
                ->first();

            if (!$sc) {
                $message = '操作に失敗しました。再度行って下さい。[$material_id:$weight]';
                Log::warning($message);
                return $this->notFound([$message]);
            }
            //ブレンド実績を作成
            $blender = [
                'blended_dt' => Carbon::now('Asia/Tokyo'),
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
                if ($aad->blended == 0) {
                    $aad->Update(['blended' => 1]);
                }
            }

            $crusehd_id = $sc->crushed_id;
            if (isset($crusehd_id)) {
                $crushed  = CrushingActual::find($crusehd_id);
                if ($crushed->blended == 0) {
                    $crushed->Update(['blended' => 1]);
                }
            }

            //紛済み在庫を減らす



            return $this->success('create success');
        });
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StockCrushedRequest $request)
    {
        $input = $request->all();
        $input['inout_direction'] = 0;
        $input['inout_date'] = Carbon::now();
        $id = StockCrushed::create($input)->id;
        return $this->success(['id' => $id]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(StockCrushedRequest $request, $id)
    {
        //
        $sc = StockCrushed::find($id);
        return $this->success(new StockCrushedResource($sc));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(StockCrushedRequest $request, $id)
    {
        //
        $sc = StockCrushed::find($id);
        return $this->success(new StockCrushedResource($sc));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(StockCrushedRequest $request, $id)
    {
        $input = $request->all();

        $sc = StockCrushed::find($id);
        if (!$sc) {
            $message = 'id :' . $id . ' 変更に失敗しました。';
            Log::warning($message);
            return $this->notFound([$message]);
        }
        $sc->Update($input);

        return $this->success('update success');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(StockCrushedRequest $request, $id)
    {

        $sc = StockCrushed::find($id);
        if (!$sc) {
            $message = "id: $id が既に削除された。";
            Log::warning($message);
            return $this->setStatusCode(204)->success('no content');
        }
        $sc->delete();
        return $this->success('deleted successfully');
    }
}
