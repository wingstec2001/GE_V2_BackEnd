<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProductionPlan;
use App\Http\Requests\Api\ProductionPlanRequest;
use App\Models\Setting;
use Carbon\Carbon;
class ProductionPlanController extends Controller
{
    public function All(Request $request)
    {
        $my_query = ProductionPlan::query();
        $date = $request['date'];
        $routerId = $request['router'];
        // $from = Carbon::createFromFormat('Y-m-d H:i:s', $date.'06:00:00'); 

        if($date!=null){
            $from = Setting::instance()->getStartDateTimeOfBusinessDate($date);
            $to = new Carbon($from);
            $to->addDays(1);
            $my_query->whereBetween('plan_date', [$from, $to]);
        }
        if($routerId!=null){
            $my_query->where('route_id',$routerId);
        }
        // foreach ($request->query() as $key => $value) {
        //     $my_query->where($key, $value);
        // }
        $result = $my_query->with('product')->orderby('plan_date', 'desc')->get();
        // $productionplans =  ProductionPlan::with('product')->orderby('plan_date', 'desc')->get();
        return $this->success($result);
    }

    public function Add(ProductionPlanRequest $request){
          try {
            $productionplan = $request->all();
            if(!$request->plan_date) {
                $productionplan['plan_date'] = Carbon::now('Asia/Tokyo');
            }        
              $id = ProductionPlan::create($productionplan)->id;
              return $this->success(['id' => $id]);
          } catch (\Exception $ex) {
              error_log($ex->getCode());
          }
    }

    public function Detail(ProductionPlanRequest $request, $id)
    {
        $productionplan =  ProductionPlan::with('product')->find($id);
        return $this->success($productionplan);
    }

    public function Update(ProductionPlanRequest $request, $id)
      {
        try { 
            $productionplan = $request->all();  
            ProductionPlan::with('product')->find($id)->Update($productionplan);
            return $this->success('update success');
        } catch (\Exception $ex) {
            error_log('--------------->Add Error:' . $ex.code . $ex.message);
            }
      }

      public function Delete(ProductionPlanRequest $request, $id)
      {
        ProductionPlan::find($id)->delete();
        return $this->success('delete success');
      }
}
