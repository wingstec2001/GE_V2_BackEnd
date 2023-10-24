<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Models\Customer;
use App\Http\Requests\Api\CustomerRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CustomerController extends Controller
{
  public function All(Request $request)
  {
    // $customers =  Customer::with('country', 'area')->get();
    $my_query = Customer::query();

    if ($request['customer_id'] != null) {
      $my_query->where('customer_id', $request['customer_id']);
    }

    if ($request['mobile'] != null) {
      $my_query->where('mobile', $request['mobile']);
    }

    if ($request['supplier'] != null) {
      $my_query->where('supplier', $request['supplier']);
    }

    $result = $my_query->orderby('id', 'desc')->with('country', 'area')->get();
    return $this->success($result);
  }

  public function MobileNums()
  {
    $MobileNums = Customer::orderby('mobile', 'asc')->pluck('mobile');
    return $this->success($MobileNums);
  }

  public function CustomerIds()
  {
    $customerIds = Customer::select('customer_id', 'customer_name')->orderby('customer_id', 'asc')->get();
    return $this->success($customerIds);
  }

  public function SupplierIds()
  {
    $supplierIds = Customer::where('supplier', 1)->select('customer_id', 'customer_name')->orderby('customer_id', 'asc')->get();
    return $this->success($supplierIds);
  }

  public function Add(CustomerRequest $request)
  {
    $customer = $request->all();
    // if ($customer['mobile'] == null ){
    //   $customer->mobile='00012345678';
    // }

    // if ($customer->email == null){
    //   $customer->email="ukonw@ge.com";
    // }


    $id = Customer::create($customer)->id;
    return $this->success(['id' => $id]);
  }

  public function Detail(CustomerRequest $request, $id)
  {
    $target = Customer::find($id);
    if (!$target) {
      $message = 'customer_id :' . $id . 'が見つかりませんでした。';
      Log::warning($message);
      return $this->notFound([$message]);
    }

    return $this->success($target);
  }

  public function Update(CustomerRequest $request, $id)
  {
    $target = Customer::find($id);
    if (!$target) {
      $message = 'customer_id :' . $id . 'が見つかりませんでした。';
      Log::warning($message);
      return $this->notFound([$message]);
    }

    $customer = $request->all();
    $ret = $target->Update($customer);

    if ($ret) {
      return $this->success('update success');
    } else {
      $message = 'customer_id :' . $id . ' 変更に失敗しました。';
      return $this->notFound([$message]);
    }
  }

  public function Delete(CustomerRequest $request, $id)
  {
    $customer = Customer::find($id);
    if (!$customer) {
      $message = 'Customer ID : ' . $id . ' が見つかりませんでした。';
      Log::warning($message);
      return $this->setStatusCode(204)->success('no content');
    }

    $customer_id = $customer->customer_id;
    $result = $this->isExist($customer_id);
    if (!$result) {
      $customer->delete();
      return $this->setStatusCode(204)->success('no content');
    } else {
      $message = 'Customer ID : ' . $id . ' が削除不可です。';
      Log::warning($message);
      return $this->failed([$message]);
    }
  }

  public function isExist($id)
  {
    $tables = ['t_arrival_details', 't_contract_pellet', 't_contract_crushed', 't_vanning'];
    foreach ($tables as $table) {
      $strSQL = 'SELECT customer_id FROM ' . $table . " WHERE customer_id='" . $id . "'";
      $result = DB::select($strSQL);
      if ($result) {
        return true;
      }
    }
    return false;
  }
}
