<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\ContractMaterial;
use App\Models\ContractMaterialDetails;
use App\Models\ViewContractMaterial;
use App\Models\Setting;
use App\Http\Requests\Api\ContractRequest;
use App\Services\DailyMaterialService;
use Illuminate\Support\Facades\Log;

class ContractMaterialController extends Controller
{
  protected $dailyMaterialService;

  public function __construct(DailyMaterialService $dailyMaterialService)
  {
    $this->dailyMaterialService = $dailyMaterialService;
  }


  public function All(Request $request)
  {
    // $contracts = Contract::with('contractDetails')->orderby('contract_date', 'desc')->get();
    // return $this->success($contracts);
    $my_query = ViewContractMaterial::query();

    if ($request['material_id'] != null) {
  
      $material_id = $request['material_id'];
      $my_query->where('material_id', '=', $material_id);
    }

    if ($request['contract_status'] != null) {
      //契約状態により検索
      $status = $request['contract_status'];
      $my_query->where('contract_status', '=', $status);
    }

    if ($request['customer_id'] != null) {
      //Customer_idにより検索
      $customer_id = $request['customer_id'];
      $my_query->where('customer_id', '=', $customer_id);
    }

    if ($request['startDate'] != null) {
      $my_query->where('contract_date', '>=', $request['startDate']);
    }
    if ($request['endDate'] != null) {
      $my_query->where('contract_date', '<=', $request['endDate']);
    }

    $contractDetails = $my_query->orderby('contract_id', 'desc')->orderby('detail_id')->get();
    return $this->success($contractDetails);
  }

  public function Add(Request $request)
  {
    return DB::transaction(function () use ($request) {
      $contract = $request->all();
      // $contract_id = $request->contract_id;
      $details = $request->contract_details;
      $contract['contract_status'] = 2;
      $contract_date = $contract['contract_date'];

      $contract = ContractMaterial::create($contract);
      foreach ($details as $v) {
        $v['contract_id'] = $contract->id;
        $material_id = $v['material_id'];

        ContractMaterialDetails::create($v);

        //t_arrival_detailsを更新　出庫先を2、ブレンド済みとする
        $sqlUpdate="UPDATE greenearth.t_arrival_details set destination=2, blended=1 ".
          " WHERE aad_id in (". $v['selected_ids'].")";

        DB::update($sqlUpdate);

        //月次出庫を再計算
         $this->dailyMaterialService->updateWeightOut($material_id,$contract_date,2);
      }

      
      

      return $this->success('add contract success');
    });
  }

  //契約IDの一覧を取得する
  public function ContractIds()
  {
    $contractIds = Contract::select('contract_id', 'contract_name')
      ->orderby('contract_id', 'asc')
      ->get();

    return $this->success($contractIds);
  }

  public function Detail(Request $request, $id)
  {
    $contract = ContractMaterial::with('contractDetails')->where('id', $id)->first();
    if (!$contract) {
      $message = "contract_id: $id が見つかりませんでした。";
      Log::warning($message);
      return $this->notFound([$message]);
    }

    return $this->success($contract);
  }

  //契約変更
  public function Update(Request $request, $id)
  {
    $find = ContractMaterial::find($id);
    if (!$find) {
      $message = "contract_id: $id が見つかりませんでした。";
      Log::warning($message);
      return $this->notFound(['変更に失敗しました。']);
    }

    return DB::transaction(function () use ($request, $find) {

      $targets = Array();
      //変更前の契約日を取得
      $former_date= $find->contract_date;
      $contract = $request->all();
      $cc_id = $request->id;

      $materialIds =  ViewContractMaterial::where('contract_id', $cc_id)->pluck('material_id');
      foreach($materialIds as $material_id ){
        $target = [
          'material_id'=>$material_id,
          'target_date'=>$former_date.' 12:00:00',
          'contract_weight' => $find->contract_weight,
        ];
        $targets[] = $target;
      
      }
      $details = $request->contract_details;
      
      $ret = $find->Update($contract);
      if ($ret) {
        $materialIds = [];
        $target_date= $contract['contract_date'];
        ContractMaterialDetails::where('contract_id', $cc_id)->delete();
        foreach ($details as $v) {
          $v['contract_id'] = $cc_id;
          ContractMaterialDetails::create($v);

          $target = [
            'material_id'=> $v['material_id'], 
            'target_date'=> $target_date.' 12:00:00',
            'contract_weight'=>$v['contract_weight'], 
          ];
          $targets[] = $target;
        }

        // //紛済み在庫情報を再計算
        //  $this->dailyMaterialService->updateDailyOutByAddDelete($targets);

        

        return $this->success('update success');
    }else {
      return $this->notFound(["更新に失敗しました。"]);
    }
    });
  }

  //契約削除
  public function Delete(Request $request, $id)
  {
    $contract = ContractMaterial::find($id);
    if (!$contract) {
      //当該契約存在しない（削除済み）場合、削除成功を返す
      $message = "Contract_id: $id が既に削除された。";
      Log::warning($message);
      return $this->setStatusCode(204)->success('no content');
    }

    return DB::transaction(function () use ($contract) {
      $contract_id = $contract->id;
      $contract_date = $contract->contract_date;

      $contract->delete();
    
      $materials =  ContractMaterialDetails::where('contract_id', $contract_id)
        ->select('material_id','contract_weight','selected_ids')->get();
  
      // //月次出庫再計算
      foreach ($materials as $material) {
        $selected_ids = $material->selected_ids;
        //t_stock_crushed を更新する
        if (strlen($selected_ids)>0) {
          $sqlUpdate="UPDATE greenearth.t_arrival_details set destination=0, blended=0 ".
          " WHERE aad_id in ($selected_ids)";
  
          DB::update($sqlUpdate); 
        }
       
        $material_id = $material->material_id;
        $this->dailyMaterialService->updateWeightOut($material_id,$contract_date,2);
       
      }

      ContractMaterialDetails::where('contract_id', $contract_id)->delete();
      
      return $this->success('delete success');
    });
  }
}
