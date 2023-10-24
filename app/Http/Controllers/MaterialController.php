<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Material;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\Api\MaterialRequest;
use Illuminate\Auth\Events\Failed;
use Illuminate\Support\Facades\Auth;

class MaterialController extends Controller
{
  // function __construct()
  // {
  //     $this->middleware('permission:material-read');
  //     $this->middleware('permission:material-create', ['only' => ['Add']]);
  //     $this->middleware('permission:material-update', ['only' => ['Update']]);
  //     $this->middleware('permission:material-delete', ['only' => ['delete']]);
  // }
  public function All(Request $request)
  {
    $my_query = Material::query();

    if ($request['material_id'] != null) {
      $my_query->where('material_id', $request['material_id']);
    }

    if ($request['material_name'] != null) {
      $my_query->where('material_name', $request['material_name']);
    }

    $result = $my_query->orderby('id', 'desc')->get();
    return $this->success($result);
  }

  public function Add(MaterialRequest $request)
  {
    $material = $request->all();

    if ($request->has('material_img')) {
      $img = $request->material_img;
      if ($img != null && !Storage::exists($img)) {
        return  $this->failed(['file not found'], 422);
      }
    }
    // $material['created_by'] = Auth::guard('api')->user()->id;
    // dd($material);
    $material['material_id'] = strtoupper($material['material_id']);
    $id = Material::create(
      $material
    )->id;
    return $this->success(['id' => $id]);
  }

  public function MaterialIds()
  {
    try {
      $materialIds = Material::select('material_id', 'material_name')->orderby('material_id')->get();
      return $this->success($materialIds);
    } catch (\Exception $ex) {
      Log::emergency($ex);
    }
  }

  public function Detail(Request $request, $id)
  {
    $material =  Material::find($id);
    return $this->success($material);
  }

  public function Update(MaterialRequest $request, $id)
  {
    $materials = $request->all();
    error_log('--------------->GET Request edit material:' . json_encode($materials));

    $material = Material::find($id);
    if ($request->has('material_img')) {
      $img = $request->material_img;
      $old_img = $material->material_img;
      if ($img != null && !Storage::exists($img)) {
        error_log('--------------->file not found:' . $img);
        return  $this->failed(['img not found'], 422);
      }
      // add new img or delete img 
      if ($img != $old_img && Storage::exists($old_img)) {
        Storage::delete($old_img);
      }
    }

    // $material['updated_by'] = Auth::guard('api')->user()->id;
    $material->Update($materials);

    return $this->success('update success');
  }

  // public function Delete(MaterialRequest $request, $id)
  // {
  //     $material =  Material::find($id);
  //     $res = Material::destroy($id);
  //     if($res){
  //         $img = $material->material_img;
  //         if (Storage::exists($img)) {
  //             Storage::delete($img);
  //     }}else{
  //         return $this->failed('data cannot delete');
  //     }
  //     // $material->delete();
  //     return $this->success('delete success');
  //     // return $this->success('messages.welcome');
  // }

  public function Delete(MaterialRequest $request, $id)
  {
    try {
      $material = Material::find($id);
      $material_id = $material->material_id;
      $result = $this->isExist($material_id);
      if (!$result) {
        $img = $material->material_img;
        if (Storage::exists($img)) {
          Storage::delete($img);
        }

        $material->delete();
        return $this->success('delete success');
      } else {
        return $this->failed(['delete failed']);
      }
    } catch (\Exception $ex) {
      error_log($ex->getCode());
    }
  }

  public function isExist($id)
  {
    //  $tables = ['t_arrival_details', 't_arrival_plan', 't_crushing_actual', 't_crushing_plan'];
    $tables = ['t_arrival_details', 't_crushing_actual', 't_contract_crushed_details'];
    foreach ($tables as $table) {
      $strSQL = 'SELECT * FROM ' . $table . " WHERE material_id='" . $id . "'";
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
      'material_img' => 'required|mimes:jpg,jpeg,bmp,png',
    ]);

    if ($validator->fails()) {
      return response()->json($validator->errors(), 422);
    }

    // path = /img/material_img/xxx.ext
    $path = '/' . $request->file('material_img')->store('/images/material_img');
    error_log('--------------->save photo path:' . $path);
    return $this->success($path);
  }
}
