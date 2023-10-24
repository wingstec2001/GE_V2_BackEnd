<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Area;
use App\Http\Requests\Api\AreaRequest;
use Illuminate\Support\Facades\Log;

class AreaController extends Controller
{
    public function All(Request $request)
    {
        // $areas =  Area::all();
        $my_query = Area::query();

        if ($request['area_id'] != null) {
            $my_query->where('area_id', $request['area_id']);
        }

        if ($request['area_name'] != null) {
            $my_query->where('area_name', $request['area_name']);
        }
       
        $result = $my_query->get();
        return $this->success($result);
    }

    public function Add(AreaRequest $request)
    {
        $area = $request->all();
        $id = Area::create($area)->id;
        return $this->success(['id' => $id]);
    }

    public function AreaIds()
    {
        $areaIds = Area::select('area_id', 'area_name')->orderby('area_id', 'asc')->get();
        return $this->success($areaIds);
    }

    public function Detail(AreaRequest $request, $id)
    {
        $area =  Area::find($id);
        if (!$area) {
            $message = 'area_id : ' . $id . ' が見つかりませんでした。';
            Log::warning($message);
            return $this->notFound([$message]);
        }

        return $this->success($area);
    }

    public function Update(AreaRequest $request, $id)
    {
        $target =  Area::find($id);
        if (!$target) {
            $message = 'area_id : ' . $id . ' が見つかりませんでした。';
            Log::warning($message);
            return $this->notFound([$message]);
        }
        $area = $request->all();
        $ret = $target->Update($area);

        if (!$ret) {
            $message = 'area_id : ' . $id . ' 変更に失敗しました。';
            Log::warning($message);
            return $this->notFound([$message]);
        }

        return $this->success('update success');
    }

    public function Delete(Request $request, $id)
    {
        $area =  Area::find($id);
        if (!$area) {
            $message = 'Area ID : ' . $id . ' が見つかりませんでした。';
            Log::warning($message);
            return $this->setStatusCode(204)->success('no content');
        }

        $area_id = $area->area_id;
        $result = $this->isExist($area_id);
        if (!$result) {
            $area->delete();
            return $this->setStatusCode(204)->success('no content');
        }else{
            //使用中の場合、削除不可とする
            return $this->failed(['delete failed']);
        }
       
    }

    public function isExist($id)
    {
        $tables = ['m_customer'];
        foreach ($tables as $table) {
            $strSQL = 'SELECT area_id FROM ' . $table . " WHERE area_id='" . $id . "'";
            $result = DB::select($strSQL);
            if ($result) {
                return true;
            }
        }
        return false;
    }
}
