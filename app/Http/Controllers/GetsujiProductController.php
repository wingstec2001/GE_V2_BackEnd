<?php

namespace App\Http\Controllers;

use App\Http\Requests\Api\GetsujiProductRequest;
use App\Models\GetsujiProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GetsujiProductController extends Controller
{
  public function All(Request $request)
  {
    $target_ym = $request['selMonth'];
    $getsujiProduct =  GetsujiProduct::where('yyyymm', $target_ym)->orderby('product_id', 'asc')->get();
    return $this->success($getsujiProduct);
  }
    
  public function Detail(Request $request, $id)
  {
    $getsujiProduct =  GetsujiProduct::find($id);
    if (!$getsujiProduct) {
      $message = 'id :' . $id . ' が見つかりませんでした。';
      Log::warning($message);
      return $this->notFound([$message]);
    }

    return $this->success($getsujiProduct);
  }

  public function Update(GetsujiProductRequest $request, $id)
  {
    $getsujiProduct = GetsujiProduct::find($id);
    if (!$getsujiProduct) {
      $message = 'id :' . $id . ' が見つかりませんでした。';
      Log::warning($message);
      return $this->notFound([$message]);
    }

    $new_getsujiProduct = $request->all();
    $ret = $getsujiProduct->Update($new_getsujiProduct);
    if (!$ret) {
      $message = 'id :' . $id . ' 変更に失敗しました。';
      Log::warning($message);
      return $this->notFound([$message]);
    }

    return $this->success('update success');
  }
}
