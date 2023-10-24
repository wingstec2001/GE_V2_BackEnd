<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CrushingPlan;
use App\Models\ArrivalActualDetail;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PerformCrushingController extends Controller
{

    /**
     * perform crushing
     * 
     * change crushing_status to 1  
     * add the to curshing_plan
     *
     * @param  \Illuminate\Http\Request  $request # ViewArriavelInfo
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        return DB::transaction(function () use ($request) {
            foreach ($request->all() as $input) {
                $aad = ArrivalActualDetail::find($input['aad_id']);
                $id =  $input['aad_id'];
                if($aad==null)
                {   
                    $message = "aad_id: $id が存在しないため、粉砕できません。";
                    Log::warning($message);
                    return $this->notFound([$message]);
                }
                if($aad->processed==1)
                {
                    $message = "aad_id: $id が既に粉砕された。";
                    Log::warning($message);
                    return $this->setStatusCode(204)->success('no content');
                }
                $aad->update(['processed' => 1]);
               
                $curshingPlan = [
                    'aad_id' => $aad->aad_id,
                    'material_id' => $aad->material_id,
                    'plan_dt' =>  Carbon::now(),
                    'plan_weight' => $aad->arrival_weight,
                ];
                CrushingPlan::create($curshingPlan);
            }
            return $this->success('success');
        });
    }
}
