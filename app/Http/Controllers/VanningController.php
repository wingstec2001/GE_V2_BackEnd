<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Http\Requests\Api\VanningRequest;
use App\Models\Vanning;
use App\Models\Contract;
use App\Models\Setting;
use App\Models\VanningDetails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

class VanningController extends Controller
{
  public function All(Request $request)
  {
      // $vannings =  Vanning::with('vanningDetails')->orderby('vanning_date', 'desc')->orderby('vanning_time', 'asc')->get();
      $my_query = Vanning::query();

      if ($request['contract_id'] != null) {
        //Contract_idにより検索
        $contract_id = $request['contract_id'];
        $my_query->where('contract_id', '=', $contract_id);
      }

      if ($request['customer_id'] != null) {
          //Customer_idにより検索
          $customer_id = $request['customer_id'];
          $my_query->where('customer_id', '=', $customer_id);
      }

      if ($request['startDate'] != null) {
        $my_query->where('vanning_date', '>=', $request['startDate']);
      }
      if ($request['endDate'] != null) {
        $my_query->where('vanning_date', '<=', $request['endDate']);
      }

      $vannings =  $my_query->with('vanningDetails')->orderby('vanning_date', 'desc')->orderby('vanning_time', 'asc')->get();
      return $this->success($vannings);
  }

  public function Add(VanningRequest $request)
  {
    return DB::transaction(function () use ($request) {
      $vanning = $request->all();
      $vanning['vanning_status'] = 0;
      $contract_id = $request->contract_id;
      $details = $request->vanning_details;
      $vanning = Vanning::create($vanning);
      $vanning_id = $vanning->vanning_id;

      foreach($details as $v) {
        $v['vanning_id'] = $vanning_id;
        VanningDetails::create($v);
      }
      
      Contract::where('contract_id', $contract_id)->update(['contract_status' => 3]);
      
      return $this->success("add success");
    });
  }

  public function Detail(VanningRequest $request, $vanning_id)
  {
    $vanning = Vanning::with('vanningDetails')->find($vanning_id);
    if (!$vanning) {
      $message = "vannning_id: $vanning_id が見つかりませんでした。";
      Log::warning($message);
      return $this->notFound([$message]);
    }

    return $this->success($vanning);
  }

  public function Update(VanningRequest $request, $vanning_id) 
  {
    $old_vanning = Vanning::find($vanning_id);
    if (!$old_vanning) {
      $message = "vannning_id: $vanning_id が見つかりませんでした。";
      Log::warning($message);
      return $this->notFound([$message]);
    }
    return DB::transaction(function () use ($request, $vanning_id, $old_vanning) {
      $vanning = $request->all();
      $updated_time = Carbon::parse($request->updated_at)->setTimezone('Asia/Tokyo')->toDateTimeString();
      $contract_id = $request->contract_id;
      $old_contract_id = $old_vanning->contract_id;
      $old_updated_time = $old_vanning->updated_at->toDateTimeString();
      $contract_status = Contract::where('contract_id', $contract_id)->value('contract_status');
      $old_contract_status = Contract::where('contract_id', $old_contract_id)->value('contract_status');
      $details = $request->vanning_details;

      //vanningが変更された場合、変更に失敗します
      if ($updated_time !== $old_updated_time) {
        $message = "vanning_id: $vanning_id が変更されたため、変更に失敗しました。";
        Log::warning($message);
        return $this->failed([$message]);
      }

      //contract_idを変更しない場合
      if ($contract_id === $old_contract_id) {
        if ($contract_status === 3) {
          Vanning::find($vanning_id)->Update($vanning);
          foreach($details as $v) {
            if ($v['vd_id']) {
              VanningDetails::find($v['vd_id'])->Update($v);
            } else {
              VanningDetails::where('vanning_id', $vanning_id)->delete();
              VanningDetails::create($v);
            }
          }

          return $this->success("update success");
        }
      } else  {

        //contract_idを変更する場合
        if ($contract_status === 2 && $old_contract_status === 3) {
          Vanning::find($vanning_id)->Update($vanning);
          VanningDetails::where('vanning_id', $vanning_id)->delete();
          Contract::where('contract_id', $old_contract_id)->update(['contract_status' => 2]);
          foreach($details as $v) {
              VanningDetails::create($v);
            }
          Contract::where('contract_id', $contract_id)->update(['contract_status' => 3]);

          return $this->success("update success");
        }
      }
      
    });
  }
}
