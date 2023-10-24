<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ArrivalPlan;
use App\Http\Requests\Api\ArrivalPlanRequest;
use Illuminate\Support\Facades\Log;
use App\Models\Setting;

class ArrivalPlanController extends Controller
{
    public function All(Request $request)
    {
        // $arrivalplans =  ArrivalPlan::with('product', 'customer')->orderby('plan_date', 'desc')->get();
        // return $this->success($arrivalplans);
        $my_query = ArrivalPlan::query();
        if($request['start_date']!=null){
            // $date = Setting::instance()->getStartDateTimeOfBusinessDate($request['start_date']);
            $my_query->where('plan_date', '>=',$request['start_date']);
        }
        if($request['end_date']!=null){
            // $date = Setting::instance()->getStartDateTimeOfBusinessDate($request['end_date'])->addDay();
            // var_dump($date->format('Y-m-d H:i:s'));
            $my_query->where('plan_date', '<=',$request['end_date']);
        }
        if($request['customer_id']!=null){
            $my_query->where('customer_id',$request['customer_id']);
        }
        if($request['sort']!=null){
            $params = explode("-", $request['sort']);
            $my_query->orderby( $params[0], $params[1]);
   
        }else{
            $my_query->orderby('created_at', 'desc');
        }
        $result = $my_query->with('customer')->get();
        return $this->success($result);
    }


    public function Details(Request $request)
    {
        $date = $request['date'];
        $from = date('Y-m-01', strtotime($date));
        $to = date("Y-m-d", strtotime("$from +1 month -1 day"));
        $arrivalplans = ArrivalPlan::with('product', 'customer')
            ->whereBetween('plan_date', [$from, $to])->get();

        return $this->success($arrivalplans);
    }


    public function Add(ArrivalPlanRequest $request)
    {
        $arrivalplan = $request->all();

        $id = ArrivalPlan::create($arrivalplan)->id;
        return $this->success(['id' => $id]);
    }

    public function Detail(ArrivalPlanRequest $request, $id)
    {
        $arrivalplan =  ArrivalPlan::with('product', 'customer')->find($id);
        return $this->success($arrivalplan);
    }

    public function Update(ArrivalPlanRequest $request, $id)
    {
        $arrivalplan = $request->all();
        $arrivalPlan = ArrivalPlan::find($id);
        if (!$arrivalPlan) {
            $message = 'id :' . $id . ' 変更に失敗しました。';
            Log::warning($message);
            return $this->notFound([$message]);
        }
        $arrivalPlan->Update($arrivalplan);
        return $this->success('update success');
    }

    public function Delete(ArrivalPlanRequest $request, $id)
    {
        $arrivalPlan = ArrivalPlan::find($id);
        if (!$arrivalPlan) {
            $message = 'id :' . $id . ' 既に削除されました。';
            Log::warning($message);
            return $this->notFound([$message]);
        }
        $arrivalPlan->delete();
        return $this->setStatusCode(204)->success('no content');
    }
}
