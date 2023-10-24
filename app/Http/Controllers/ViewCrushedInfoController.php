<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ViewCrushedInfo;
use App\Models\Setting;
use Carbon\Carbon;

class ViewCrushedInfoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function DayDetails(Request $request)
    {
        $my_query = ViewCrushedInfo::query();

        $wuf = $request['wuf'];

        if (strcmp($request['wuf'], 'mobile') == 0) {
            $now = Carbon::now('Asia/Tokyo');
            $from = Setting::instance()->getBusinessDate($now);

            $to =  Carbon::instance($from)->addDay();

            $my_query->where('actual_date', '>=', $from);
            $my_query->where('actual_date', '<', $to);

            $from_str = $from->format('Y-m-d H:i:s');
            $to_str = $to->format('Y-m-d H:i:s');
        }

        $result = $my_query->orderby('crushed_id', 'desc')->get()->toArray();
        return $this->success($result);
    }


    // 2022.04.23 粉砕実績当日集計
    public function DaySum(Request $request)
    {
        $my_query = ViewCrushedInfo::query();
        $now = Carbon::now('Asia/Tokyo');
        $from = Setting::instance()->getBusinessDate($now);
        $to =  Carbon::instance($from)->addDay();

        $totals = ViewCrushedInfo::orderby('material_id', 'asc')
            ->where('actual_date', '>=', $from)
            ->where('actual_date', '<', $to)
            ->groupBy('material_id')
            ->select('material_id',  ViewCrushedInfo::raw("sum(actual_weight) as actual_weight"))
            ->get();

        return $this->success($totals);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
