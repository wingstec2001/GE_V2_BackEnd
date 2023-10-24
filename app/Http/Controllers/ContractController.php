<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\ContractPellet;
use App\Models\ViewContractAll;
use App\Models\ContractPelletDetails;
use App\Models\Setting;
use App\Http\Requests\Api\ContractRequest;
use App\Services\DailyProductService;
use Illuminate\Support\Facades\Log;

class ContractController extends Controller
{
  protected $dailyproductService;

  public function __construct(DailyProductService $dailyProductService)
  {
    $this->dailyproductService = $dailyProductService;
  }


  public function All(Request $request)
  {
    // $contracts = Contract::with('contractDetails')->orderby('contract_date', 'desc')->get();
    // return $this->success($contracts);
    $my_query = ViewContractAll::query();

    if ($request['contract_id'] != null) {
      //Contract_idにより検索
      $contract_id = $request['contract_id'];
      $my_query->where('contract_id', '=', $contract_id);
    }

    if ($request['product_id'] != null) {
      //Product_idにより検索
      $product_id = $request['product_id'];
      $my_query->where('product_id', '=', $product_id);
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

  public function Add(ContractRequest $request)
  {
    return DB::transaction(function () use ($request) {
      $contract = $request->all();
      // $contract_id = $request->contract_id;
      $details = $request->contract_details;
      $contract['contract_status'] = 2;
      $contractPellet = ContractPellet::create($contract);

      $cp_id = $contractPellet->id;
      foreach ($details as $v) {
        $v['contract_id'] = $cp_id;
        ContractPelletDetails::create($v);
      }

      //月次出庫を再計算
      $this->dailyproductService->updateDailyOut($cp_id);

      return $this->success('add contract success');
    });
  }

  //契約IDの一覧を取得する
  public function ContractIds()
  {
    $contractIds = ContractPellet::select('contract_id', 'contract_name')
      ->orderby('contract_id', 'asc')
      ->get();

    return $this->success($contractIds);
  }

  public function Detail(ContractRequest $request, $id)
  {
    $contract = ContractPellet::with('ContractDetails')->where('id', $id)->first();
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
    $find = ContractPellet::find($id);
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
      $cp_id = $request->id;

      $productids =  ViewContractAll::where('contract_id', $cp_id)->pluck('product_id');
      foreach($productids as $productid ){
        $target = ['product_id'=>$productid, 'target_date'=>$former_date];
        $targets[] = $target;
      
      }
      $details = $request->contract_details;
      
      $ret = $find->Update($contract);
      if ($ret) {
        $productids = [];
        $target_date= $contract['contract_date'];
        ContractPelletDetails::where('contract_id', $cp_id)->delete();
        foreach ($details as $v) {
          $v['contract_id'] = $cp_id;
          ContractPelletDetails::create($v);

          $target = ['product_id'=> $v['product_id'], 'target_date'=>$target_date];
          
          $this->dailyproductService->updateDailyOutByAddDelete($target);
        }

     
        return $this->success('update success');
        
      }else {
        return $this->notFound(["更新に失敗しました。"]);
      }
    });
  }

  //契約削除
  public function Delete(Request $request, $id)
  {
    $contract = ContractPellet::find($id);
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

      $productids =  ContractPelletDetails::where('contract_id', $contract_id)
        ->select('product_id')->get();

      ContractPelletDetails::where('contract_id', $contract_id)->delete();

      // //月次出庫再計算
      foreach ($productids as $product) {
        $this->dailyproductService->updateWeightOut($product->product_id, $contract_date);
      }

      return $this->success('delete success');
    });
  }
}
