<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Http\Requests\Api\ProductRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
  public function All(Request $request)
  {
    // $products =  Product::all();
    $my_query = Product::query();

    if ($request['product_id'] != null) {
      $my_query->where('product_id', $request['product_id']);
    }

    if ($request['product_name'] != null) {
      $my_query->where('product_name', $request['product_name']);
    }

    $result = $my_query->orderby('id', 'desc')->get();
    
    return $this->success($result);
  }

  public function Add(ProductRequest $request)
  {
    $product = $request->all();

    if ($request->has('product_img')) {
      $img = $request->product_img;
      if ($img != null && !Storage::exists($img)) {
        $message = "$img が見つかりませんでした。";
        Log::warning($message);
        return  $this->failed([$message], 422);
      }
    }
    $product['product_id']=strtoupper($product['product_id']);
    $id = Product::create($product)->id;
    return $this->success(['id' => $id]);
  }

  public function ProductIds()
  {
    $productIds = Product::select('product_id', 'product_name')->orderby('product_id', 'asc')->get();
    return $this->success($productIds);
  }

  public function Detail(ProductRequest $request, $id)
  {
    $product =  Product::find($id);
    if ($product) {
      return $this->success($product);
    } else {
      $message = 'product_id:' . $id . ' is not found';
      Log::warning($message);
      return $this->notFound([' 選択されたidは無効です。']);
    }
  }

  public function Update(ProductRequest $request, $id)
  {
    $target = Product::find($id);
    if (!$target) {
      $message = 'product_id:' . $id . ' 変更に失敗しました。';
      Log::warning($message);
      return $this->notFound([$message]);
    }
    $product = $request->all();

    if ($request->has('product_img')) {
      $img = $request->product_img;
      $old_img = $target->product_img;
      if ($img != null && !Storage::exists($img)) {
        $message = '$img が見つかりませんでした。';
        Log::warning($message);
        return $this->failed([$message], 422);
      }
      if ($img != $old_img && Storage::exists($old_img)) {
        Storage::delete($old_img);
      }
    }

    $ret = $target->Update($product);

    if ($ret) {
      return $this->success('update success');
    } else {
      return $this->notFound();
    }
  }

  public function Delete(Request $request, $id)
  {
    $product = Product::find($id);
    if (!$product) {
      $message = 'Product ID:' . $id . ' が見つかりませんでした。';
      Log::warning($message);
      return $this->setStatusCode(204)->success('no content');
    }

    $product_id = $product->product_id;
    $result = $this->isExist($product_id);
    if (!$result) {
      $img = $product->product_img;
      if (Storage::exists($img)) {
        Storage::delete($img);
      }

      $product->delete();
      return $this->setStatusCode(204)->success('no content');
    } else {
      return $this->failed(['delete failed']);
    }
  }

  public function isExist($id)
  {
    $tables = ['t_contract_pellet_details', 't_production'];
    foreach ($tables as $table) {
      $strSQL = 'SELECT product_id FROM ' . $table . " WHERE product_id='" . $id . "'";
      $result = DB::select($strSQL);
      if ($result) {
        return true;
      }
    }
    return false;
  }

  public function Photo(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'product_img' => 'required|mimes:jpeg,bmp,png,gif',
    ]);

    if ($validator->fails()) {
      return response()->json($validator->errors(), 422);
    }
    $path = '/' . $request->file('product_img')->store('/images/product_img');

    return $this->success($path);
  }
}
