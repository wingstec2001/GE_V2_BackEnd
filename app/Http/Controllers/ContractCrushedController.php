<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\ContractCrushed;
use App\Models\ViewContractCrushed;
use App\Models\ContractCrushedDetails;
use App\Models\Setting;
use App\Http\Requests\Api\ContractRequest;
use App\Services\DailyCrushedService;
use Illuminate\Support\Facades\Log;

class ContractCrushedController extends Controller
{
    protected $dailycrushedService;

    public function __construct(DailyCrushedService $dailyCrushedService)
    {
        $this->dailycrushedService = $dailyCrushedService;
    }

    public function All(Request $request)
    {
        // $contracts = Contract::with('contractDetails')->orderby('contract_date', 'desc')->get();
        // return $this->success($contracts);
        $my_query = ViewContractCrushed::query();

        // if ($request['contract_id'] != null) {
        //   //Contract_idにより検索
        //   $contract_id = $request['contract_id'];
        //   $my_query->where('contract_id', '=', $contract_id);
        // }

        if ($request['material_id'] != null) {
            //Product_idにより検索
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

        $contractDetails = $my_query
            ->orderby('contract_id', 'desc')
            ->orderby('detail_id')
            ->get();
        return $this->success($contractDetails);
    }

    //紛済み出庫をキャンセルする
    private function CancelContractCrushed($stockedIds, $material_id, $contract_date)
    {
        //t_stock_crushed を更新する 未出庫にする
        $sqlUpdate = 'UPDATE greenearth.t_stock_crushed set processed=0,destination=0 ' . " WHERE id in ($stockedIds)";
        DB::update($sqlUpdate);

        $strSQL = ' SELECT aad_id, crushed_id from greenearth.t_stock_crushed ' . " WHERE id in ($stockedIds)";

        $rets = DB::select($strSQL);

        $arr_aadid = [];
        $arr_caid = [];

        foreach ($rets as $ret) {
            if (isset($ret->aad_id)) {
                $arr_aadid[] = $ret->aad_id;
            }
            if (isset($ret->crushed_id)) {
                $arr_caid[] = $ret->crushed_id;
            }
        }

        $strAad = implode(', ', $arr_aadid);
        $strCA = implode(', ', $arr_caid);

        if (!empty($strAad)) {
            $udpdateArrival =
                ' UPDATE  greenearth.t_arrival_details set blended = 0 ' . ' WHERE aad_id in (' . $strAad . ')';

            $ret = DB::update($udpdateArrival);
        }

        if (!empty($strCA)) {
            $udpdateCrushed =
                ' UPDATE  greenearth.t_crushing_actual set blended = 0 ' . ' WHERE crushed_id in (' . $strCA . ')';
            $ret = DB::update($udpdateCrushed);
        }

        //月次出庫を再計算
        $this->dailycrushedService->updateWeightOut($material_id, $contract_date, 2);
    }

    //紛済み出庫を実行する
    private function ExecuteContractCrushed($stockedIds, $material_id, $contract_date)
    {
        //t_stock_crushed を更新する
        $sqlUpdate = 'UPDATE greenearth.t_stock_crushed set processed=1,destination=2 ' . " WHERE id in ($stockedIds)";
        DB::update($sqlUpdate);

        $strSQL = ' SELECT aad_id, crushed_id from greenearth.t_stock_crushed ' . " WHERE id in ($stockedIds)";

        $rets = DB::select($strSQL);

        $arr_aadid = [];
        $arr_caid = [];

        foreach ($rets as $ret) {
            if (isset($ret->aad_id)) {
                $arr_aadid[] = $ret->aad_id;
            }
            if (isset($ret->crushed_id)) {
                $arr_caid[] = $ret->crushed_id;
            }
        }

        $strAad = implode(', ', $arr_aadid);
        $strCA = implode(', ', $arr_caid);

        if (!empty($strAad)) {
            $udpdateArrival =
                ' UPDATE  greenearth.t_arrival_details set blended=1 ' . ' WHERE aad_id in (' . $strAad . ')';

            $ret = DB::update($udpdateArrival);
        }

        if (!empty($strCA)) {
            $udpdateCrushed =
                ' UPDATE  greenearth.t_crushing_actual set blended=1 ' . ' WHERE crushed_id in (' . $strCA . ')';
            $ret = DB::update($udpdateCrushed);
        }

        //月次出庫を再計算
        $this->dailycrushedService->updateWeightOut($material_id, $contract_date, 2);
    }

    public function Add(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $contract = $request->all();
            // $contract_id = $request->contract_id;
            $details = $request->contract_details;
            $contract['contract_status'] = 2;
            $contract = ContractCrushed::create($contract);
            $contract_date = $request['contract_date'];

            $v['contract_id'] = $contract->id;
            $v['contract_weight'] = $request['contract_weight'];

            $v['contract_note'] = $request['contract_note'];
            $v['contract_price'] = $request['contract_price'];
            $v['material_id'] = $request['material_id'];
            $v['stocked_ids'] = $request['stocked_ids'];

            $ret = ContractCrushedDetails::create($v);

            //t_stock_crushed を更新する
            $sqlUpdate =
                'UPDATE greenearth.t_stock_crushed set processed=1,destination=2 ' .
                ' WHERE id in (' .
                $v['stocked_ids'] .
                ')';
            DB::update($sqlUpdate);

            $strSQL =
                ' SELECT aad_id, crushed_id from greenearth.t_stock_crushed ' .
                ' WHERE id in (' .
                $v['stocked_ids'] .
                ')';

            $rets = DB::select($strSQL);

            $arr_aadid = [];
            $arr_caid = [];

            foreach ($rets as $ret) {
                if (isset($ret->aad_id)) {
                    $arr_aadid[] = $ret->aad_id;
                }
                if (isset($ret->crushed_id)) {
                    $arr_caid[] = $ret->crushed_id;
                }
            }

            $strAad = implode(', ', $arr_aadid);
            $strCA = implode(', ', $arr_caid);

            if (!empty($strAad)) {
                $udpdateArrival =
                    ' UPDATE  greenearth.t_arrival_details set blended=1 ' . ' WHERE aad_id in (' . $strAad . ')';

                $ret = DB::update($udpdateArrival);
            }

            if (!empty($strCA)) {
                $udpdateCrushed =
                    ' UPDATE  greenearth.t_crushing_actual set blended=1 ' . ' WHERE crushed_id in (' . $strCA . ')';
                $ret = DB::update($udpdateCrushed);
            }

            $material_id = $v['material_id'];
            //月次出庫を再計算
            $this->dailycrushedService->updateWeightOut($material_id, $contract_date, 2);

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
        // $contractDetail = ContractCrushedDetails::find($id);
        $contract = ViewContractCrushed::where('did', $id)->first();
        if (!$contract) {
            $message = "cc_id: $id が見つかりませんでした。";
            Log::warning($message);
            return $this->notFound([$message]);
        }

        $contract_note = $contract['contract_note'];
        $stockedIds = $contract['stocked_ids'];
        if (isset($stockedIds)) {
            $strSQL =
                ' SELECT id,material_id, aad_id, crushed_id,crushed_weight,origin from greenearth.v_stock_crushed_all ' .
                ' WHERE id in (' .
                $stockedIds .
                ') order by id asc';

            $rets = DB::select($strSQL);

            //契約済み分を返す
            $contract['contracted_details'] = $rets;
        }

        return $this->success($contract);
    }

    //契約変更
    public function Update(Request $request, $id)
    {
        $find = ContractCrushedDetails::find($id);
        if (!$find) {
            $message = "cc_did: $id が見つかりませんでした。";
            Log::warning($message);
            return $this->notFound(['変更に失敗しました。']);
        }

        $contract_id = $find->contract_id;
        $find_contract = ContractCrushed::find($contract_id);
        if (!$find_contract) {
            $message = "contract_id: $contract_id が見つかりませんでした。";
            Log::warning($message);
            return $this->notFound(['変更に失敗しました。']);
        }

        return DB::transaction(function () use ($request, $find, $find_contract) {
            $contract_id = $find->contract_id;
            $targets = [];
            //変更前の契約日を取得
            $former_date = $find_contract->contract_date;
            $former_stockedIds = $find->stocked_ids;
            $former_conract_weight = $find->contract_weight;
            $material_id = $find->material_id;

            //出庫済みの粉砕在庫を未出庫にする、
            $ret = $find->Update(['contract_weight' => 0]);
            $this->CancelContractCrushed($former_stockedIds, $material_id, $former_date);

            $stockedIds = $request['stocked_ids'];

            //紛済み出庫を更新する
            $ret = $find->Update([
                'contract_note' => $request['contract_note'],
                'contract_price' => $request['contract_price'],
                'contract_weight' => $request['contract_weight'],
                'stocked_ids' => $stockedIds,
            ]);
            $contract_date = $request['contract_date'];
            $find_contract->update([
                'contract_date' => $contract_date,
                'customer_id' => $request['customer_id'],
            ]);

            $this->ExecuteContractCrushed($stockedIds, $material_id, $contract_date);

            return $this->success('update success');
        });
    }

    //契約削除
    public function Delete(Request $request, $id)
    {
        $find = ContractCrushedDetails::find($id);

        if (!$find) {
            //当該契約存在しない（削除済み）場合、削除成功を返す
            $message = "cc_did: $id が既に削除された。";
            Log::warning($message);
            return $this->setStatusCode(204)->success('no content');
        }

        return DB::transaction(function () use ($request, $find) {
            $contract_id = $find->contract_id;
            $find_contract = ContractCrushed::find($contract_id);

            $contract_date = $find_contract->contract_date;

            $stockedIds = $find->stocked_ids;
            $material_id = $find->material_id;

            $find->delete();
            $find_contract->delete();
            $this->CancelContractCrushed($stockedIds, $material_id, $contract_date);

            // $materials = ContractCrushedDetails::where('contract_id', $contract_id)
            //     ->select('material_id', 'contract_weight', 'stocked_ids')
            //     ->get();

            // // //月次出庫再計算
            // foreach ($materials as $material) {
            //     $stocked_ids = $material->stocked_ids;
            //     //t_stock_crushed を更新する
            //     if (strlen($stocked_ids) > 0) {
            //         $sqlUpdate =
            //             'UPDATE greenearth.t_stock_crushed set processed=0, destination=0 ' .
            //             "WHERE id in ($stocked_ids)";

            //         DB::update($sqlUpdate);

            //         $strSQL =
            //             ' SELECT aad_id, crushed_id from greenearth.t_stock_crushed ' .
            //             ' WHERE id in (' .
            //             $stocked_ids .
            //             ')';

            //         $rets = DB::select($strSQL);

            //         $arr_aadid = [];
            //         $arr_caid = [];

            //         foreach ($rets as $ret) {
            //             if (isset($ret->aad_id)) {
            //                 $arr_aadid[] = $ret->aad_id;
            //             }
            //             if (isset($ret->crushed_id)) {
            //                 $arr_caid[] = $ret->crushed_id;
            //             }
            //         }

            //         $strAad = implode(', ', $arr_aadid);
            //         $strCA = implode(', ', $arr_caid);

            //         if (!empty($strAad)) {
            //             $udpdateArrival =
            //                 ' UPDATE  greenearth.t_arrival_details set blended=0 ' .
            //                 ' WHERE aad_id in (' .
            //                 $strAad .
            //                 ')';

            //             $ret = DB::update($udpdateArrival);
            //         }

            //         if (!empty($strCA)) {
            //             $udpdateCrushed =
            //                 ' UPDATE  greenearth.t_crushing_actual set blended=0 ' .
            //                 ' WHERE crushed_id in (' .
            //                 $strCA .
            //                 ')';
            //             $ret = DB::update($udpdateCrushed);
            //         }
            //     }

            //     $this->dailycrushedService->updateWeightOut($material->material_id, $contract_date, 2);
            // }

            // ContractCrushedDetails::where('contract_id', $contract_id)->delete();

            return $this->success('delete success');
        });
    }
}
