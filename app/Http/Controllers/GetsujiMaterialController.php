<?php

namespace App\Http\Controllers;

use App\Http\Requests\Api\GetsujiMaterialRequest;
use App\Models\GetsujiMaterial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GetsujiMaterialController extends Controller
{  
  public function All(Request $request)
  {
    $target_ym = $request['selMonth'];
    $getsujiMaterial =  GetsujiMaterial::where('yyyymm', $target_ym)->orderby('material_id', 'asc')->get();
    return $this->success($getsujiMaterial);
  }
    
  public function Detail(Request $request, $id)
  {
    $getsujiMaterial =  GetsujiMaterial::find($id);
    if (!$getsujiMaterial) {
      $message = 'id :' . $id . ' が見つかりませんでした。';
      Log::warning($message);
      return $this->notFound([$message]);
    }

    return $this->success($getsujiMaterial);
  }

  public function Update(GetsujiMaterialRequest $request, $id)
  {
    $getsujiMaterial = GetsujiMaterial::find($id);
    if (!$getsujiMaterial) {
      $message = 'id :' . $id . ' が見つかりませんでした。';
      Log::warning($message);
      return $this->notFound([$message]);
    }

    $new_getsujiMaterial = $request->all();
    $ret = $getsujiMaterial->Update($new_getsujiMaterial);
    if (!$ret) {
      $message = 'id :' . $id . ' 変更に失敗しました。';
      Log::warning($message);
      return $this->notFound([$message]);
    }

    return $this->success('update success');
  }
}
