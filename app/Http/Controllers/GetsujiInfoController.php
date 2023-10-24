<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Http\Requests\Api\GetsujiInfoRequest;
use App\Models\GetsujiInfo;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GetsujiInfoController extends Controller
{
  public function All(Request $request)
  {
    $Month = $request->query();
    $GetsujiInfo =  GetsujiInfo::orderby('product_id', 'asc')->orderby('yyyymm', 'desc')->get();
    return $this->success($GetsujiInfo);
  }

  public function Add(GetsujiInfoRequest $request)
  {

    $getsujiInfo = $request->all();
    GetsujiInfo::create($getsujiInfo);
    return $this->success('success');
  }

  public function Detail(Request $request, $id)
  {
    $getsujiInfo =  GetsujiInfo::find($id);
    if (!$getsujiInfo) {
      $message = 'getsuji_id :' . $id . ' が見つかりませんでした。';
      Log::warning($message);
      return $this->notFound([$message]);
    }

    return $this->success($getsujiInfo);
  }

  public function Update(GetsujiInfoRequest $request, $id)
  {
    $getsujiInfo = GetsujiInfo::find($id);
    if (!$getsujiInfo) {
      $message = 'getsuji_id :' . $id . ' が見つかりませんでした。';
      Log::warning($message);
      return $this->notFound([$message]);
    }

    $new_getsujiInfo = $request->all();
    $ret = $getsujiInfo->Update($new_getsujiInfo);
    if (!$ret) {
      $message = 'getsuji_id :' . $id . ' 変更に失敗しました。';
      Log::warning($message);
      return $this->notFound([$message]);
    }

    return $this->success('update success');
  }
}
