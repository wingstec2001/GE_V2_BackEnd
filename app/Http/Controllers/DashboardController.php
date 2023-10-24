<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Http\Requests\Api\DashboardRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Models\Dashboard;
use App\Models\DashboardDetails;
use Illuminate\Http\Request;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
  public function All()
  {
    $dashboard =  Dashboard::orderby('created_at', 'desc')->get();
    return $this->success($dashboard);
  }

  public function Add(DashboardRequest $request)
  {
    return  DB::transaction(function () use ($request) {
      $dashboard_id = Uuid::uuid1()->toString();
      $dashboard = $request->all();
      // $details = $request->DashboardDetails;
      $dashboard['dashboard_id'] = $dashboard_id;

      if ($request->has('dashboard_image')) {
        $img = $request->dashboard_image;
        if ($img != null && !Storage::exists($img)) {

          $message = "img: $img が存在しません。";
          Log::warning($message);
          return  $this->failed([$message], 422);
        }
      }

      Dashboard::create($dashboard);

      // foreach ($details as $v) {
      //   $v['dashboard_id'] = $dashboard_id;
      //   DashboardDetails::create($v);
      // }

      return $this->success('add dashboard success');
    });
  }

  public function Detail(DashboardRequest $request, $id)
  {
    $dashboard = Dashboard::find($id);
    if(!$dashboard){
      $message = "dashboard_id: $id が無効です。";
      Log::warning($message);
      return $this->failed([$message], 422);
    }

    return $this->success($dashboard);
  }

  public function Update(DashboardRequest $request, $id)
  {
    $target = Dashboard::find($id);
    if (!$target) {
      $message = 'dashboard_id:' . $id . ' 変更に失敗しました。';
      Log::warning($message);
      return $this->notFound([$message]);
    }
    $dashboard = $request->all();
    // $details = $request->DashboardDetails;
    // $dashboard_id = $request->dashboard_id;
    
    if ($request->has('dashboard_image')) {
      $img = $request->dashboard_image;
      $old_img = $target->dashboard_image;
      if ($img != null && !Storage::exists($img)) {
        $message = "img: $img が存在しません。";
        Log::warning($message);
        return  $this->failed([$message], 422);
      }
      if ($img != $old_img && Storage::exists($old_img)) {
        Storage::delete($old_img);
      }
    }

    $ret = $target->Update($dashboard);

    if ($ret) {
      return $this->success('update success');
    } else {
      return $this->notFound();
    }
  }

  public function Delete(DashboardRequest $request, $id)
  {

    $dashboard = Dashboard::find($id);
    if (!$dashboard) {
      $message = "dashboard_id: $id が既に削除された。";
      Log::warning($message);
      return $this->setStatusCode(204)->success('no content');
    }

      // $dashboard_id = $dashboard->dashboard_id;
      $dashboard->delete();

      // $dbd = DashboardDetails::where('dashboard_id', $dashboard_id);
      // if (!$dbd->exists()) {
      //   $message = "DashboardDetails の dbd: $dashboard_id が既に削除された。";
      //   Log::warning($message);
      // } else {
      //   $dbd->delete();
      // }

      return $this->setStatusCode(204)->success('no content');
  }

  public function Photo(Request $request)
  {
    $a = $request->all();
    $validator = Validator::make($request->all(), [
      'dashboard_image' => 'required|mimes:jpeg,bmp,png,gif',
    ]);

    if ($validator->fails()) {
      return response()->json($validator->errors(), 422);
    }
    $path = '/' . $request->file('dashboard_image')->store('/images/dashboard_image');
    return $this->success($path);
  }
}
