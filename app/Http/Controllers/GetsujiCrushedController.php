<?php

namespace App\Http\Controllers;

use App\Http\Requests\Api\GetsujiCrushedRequest;
use App\Models\GetsujiCrushed;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GetsujiCrushedController extends Controller
{
    public function All(Request $request)
    {
      $target_ym = $request['selMonth'];
      $getsujiCrushed =  GetsujiCrushed::where('yyyymm', $target_ym)->orderby('material_id', 'asc')->get();
      return $this->success($getsujiCrushed);
    }
      
    public function Detail(Request $request, $id)
    {
      $getsujiCrushed =  GetsujiCrushed::find($id);
      if (!$getsujiCrushed) {
        $message = 'id :' . $id . ' が見つかりませんでした。';
        Log::warning($message);
        return $this->notFound([$message]);
      }
  
      return $this->success($getsujiCrushed);
    }
  
    public function Update(GetsujiCrushedRequest $request, $id)
    {
      $getsujiCrushed = GetsujiCrushed::find($id);
      if (!$getsujiCrushed) {
        $message = 'id :' . $id . ' が見つかりませんでした。';
        Log::warning($message);
        return $this->notFound([$message]);
      }
  
      $new_getsujiCrushed = $request->all();
      $ret = $getsujiCrushed->Update($new_getsujiCrushed);
      if (!$ret) {
        $message = 'id :' . $id . ' 変更に失敗しました。';
        Log::warning($message);
        return $this->notFound([$message]);
      }
  
      return $this->success('update success');
    }
}
