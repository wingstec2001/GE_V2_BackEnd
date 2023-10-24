<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CrushingPlan;
use App\Http\Requests\Api\CrushingPlanRequest;
use Carbon\Carbon;


class CrushingPlanController extends Controller
{
    public function All()
    {
        $crushingplans =  CrushingPlan::with('product')->orderby('plan_dt', 'desc')->get();
        return $this->success($crushingplans);
    }

    public function Add(CrushingPlanRequest $request){
          try {
            $crushingplan = $request->all();
            if(!$request->plan_dt) {
                $crushingplan['plan_dt'] = Carbon::now('Asia/Tokyo');
            }        
              $id = CrushingPlan::create($crushingplan)->id;
              return $this->success(['id' => $id]);
          } catch (\Exception $ex) {
              error_log($ex->getCode());
          }
    }

    public function Detail(CrushingPlanRequest $request, $id)
    {
        $crushingplan =  CrushingPlan::with('product')->find($id);
        return $this->success($crushingplan);
    }

    public function Update(CrushingPlanRequest $request, $id)
      {
        try { 
            $crushingplan = $request->all();   
            CrushingPlan::with('product')->find($id)->Update($crushingplan);
            return $this->success('update success');
        } catch (\Exception $ex) {
            error_log('--------------->Add Error:' . $ex.code . $ex.message);
            }
      }

      public function Delete(CrushingPlanRequest $request, $id)
      {
        CrushingPlan::find($id)->delete();
        return $this->success('delete success');
      }
}
