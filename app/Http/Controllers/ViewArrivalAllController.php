<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ViewArrivalAll;
use App\Models\Setting;

class ViewArrivalAllController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        
        $my_query = ViewArrivalAll::query();

        if ($request['product_id'] != null) {
            //Product_idにより検索
            $product_id = $request['product_id'];
            $my_query->where('product_id', '=', $product_id);
        }

        if ($request['crushing_status'] != null) {
            //粉砕状態により検索
            $status = $request['crushing_status'];
            $my_query->where('crushing_status', '=', $status);
        }

        if ($request['customer_id'] != null) {
            //Product_idにより検索
            $customer_id = $request['customer_id'];
            $my_query->where('customer_id', '=', $customer_id);
        }

        if ($request['startDate'] != null) {
            $date = Setting::instance()->getStartDateTimeOfBusinessDate($request['startDate']);
            $my_query->where('actual_date', '>=', $date);
        }
        if ($request['endDate'] != null) {
            $date = Setting::instance()->getStartDateTimeOfBusinessDate($request['endDate'])->addDay();
            $my_query->where('actual_date', '<=', $date);
        }

        $arrivalactuals = $my_query->orderby('arrival_id', 'desc')->get();
        return $this->success($arrivalactuals);
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
