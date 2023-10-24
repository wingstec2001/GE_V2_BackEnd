<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Reservations;

class ReservationsController extends Controller
{
    public function All(Request $request)
    {
        $my_query = Reservations::query();

        if ($request['reserve_id'] != null) {
            //Reserve_idにより検索
            $my_query->where('reserve_id', $request['reserve_id']);
        }
        if ($request['customer_id'] != null) {
            //customer_idにより検索
            $my_query->where('customer_id', $request['customer_id']);
        }
        if($request['start_date']!=null){
            $my_query->where('reserved_dt', '>=',$request['start_date']);
        }
        if($request['end_date']!=null){
            $my_query->where('reserved_dt', '<=',$request['end_date']);
        }

        $result = $my_query->orderby('id', 'desc')->get();
        return $this->success($result);
    }

    public function Detail(Request $request, $id) 
    {
        $reservation = Reservations::find($id);
        if(!$reservation){
          $message = "id: $id が無効です。";
          Log::warning($message);
          return $this->failed([$message], 422);
        }
    
        return $this->success($reservation);
    }

    public function update(Request $request, $id)
    {
        $target = Reservations::find($id);
        if(!$target){
          $message = "id: $id が無効です。";
          Log::warning($message);
          return $this->failed([$message], 422);
        }

        $reservation = $request->all();
        $ret = $target->Update($reservation);

        if ($ret) {
            return $this->success('update success');
          } else {
            $message = 'id :' . $id . ' 変更に失敗しました。';
            return $this->notFound([$message]);
          }
    }
}
