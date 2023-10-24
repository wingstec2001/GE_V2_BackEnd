<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Blender;
use App\Models\StockCrushed;
use App\Models\ArrivalActualDetail;
use App\Services\DailyCrushedService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PerformBlendingController extends Controller
{

    protected $dailyCrushedService;
    public function __construct(DailyCrushedService $dailyCrushedService)
    {
        $this->dailyCrushedService = $dailyCrushedService;
    }

    /**
     * perform crushing
     * 
     * change processed to 1  
     * add the to t_blender
     *
     * @param  \Illuminate\Http\Request  $request # ViewArriavelInfo
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        return  DB::transaction(function () use ($request) {
            foreach ($request->all() as $input) {
                $id =  $input['id'];
                $sc = StockCrushed::find($id);
                if ($sc == null) {
                    $message = "stockcrushed_id: $id が存在しないため、処理できません。";
                    Log::warning($message);
                    return $this->notFound([$message]);
                }
                if ($sc->processed == 1) {
                    $message = "stockcrushed_id: $id はすでに処理済です。";
                    Log::warning($message);
                    return $this->setStatusCode(204)->success('no content');
                }

                $now = Carbon::now('Asia/Tokyo');
                $blender = [
                    'material_id' => $sc->material_id,
                    'blended_weight' => $sc->crushed_weight,
                    'blended_dt' =>  $now,
                    'stock_crushed_id' => $sc->id,
                ];
                Blender::create($blender);
                
                
                $sc->update(['processed' => 1,'destination'=>1,'crushed_weight' =>0]);

                $aad_id = $sc->aad_id;

                if (isset($aad_id )){
                   $aad  = ArrivalActualDetail::find($aad_id); 
                   if (isset($aad) && $aad->blended == 0 ) {
                        $aad->Update(['blended'=>1]);
                   } 
                }

                $this->dailyCrushedService->updateWeightOut($sc->material_id, $now,1);
            }
            return $this->success('success');
        });
    }
}
