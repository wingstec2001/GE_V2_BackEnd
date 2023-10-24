<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

//月次入出庫
class MonthlyStockController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $type = $request['type'];
        $target_ym = $request['selMonth'];
        $startdate_tm = Carbon::createFromFormat('Ymd His', $target_ym . '01 000000');
        $startdate_lm = Carbon::instance($startdate_tm)->addMonth(-1);
        $startdate_nm = Carbon::instance($startdate_tm)->addMonth(1);
        $startDate = $startdate_tm->format('Y-m-d');
        $endDate = $startdate_nm->format("Y-m-d");
        $lastMonth = $startdate_lm->format("Ym");

        if (strcmp($type, 'product') == 0) {
            $strSQL = " SELECT m.product_id,m.product_name," .
                " COALESCE(da.weight_in,0) weight_in,COALESCE(da.weight_out,0) weight_out," .
                " COALESCE(da.weight_in-da.weight_out,0)  weight_sum," .
                ' IFNULL(g.total_weight,0) lm_weight, ' .
                ' COALESCE(da.weight_in - da.weight_out + IFNULL(g.total_weight,0), 0) sum_total ' .
                " FROM m_product m " .
                " LEFT JOIN " .
                " ( SELECT product_id,sum(weight_in) weight_in,sum(weight_out) weight_out " .
                "   FROM greenearth.t_daily_product " .
                "   WHERE target_date >= '$startDate' AND target_date < '$endDate'" .
                "   GROUP BY product_id) da " .
                "   ON m.product_id = da.product_id " .
                " LEFT JOIN (SELECT product_id,total_weight FROM greenearth.t_getsuji_product " .
                " WHERE yyyymm = '$lastMonth') g " .
                " ON m.product_id = g.product_id " .
                " order by m.product_id ";
        }

        if (strcmp($type, 'material') == 0) {
            $strSQL = " SELECT m.material_id, m.material_name," .
                " COALESCE(da.weight_in,0) weight_in," .
                " COALESCE(da.weight_out,0) weight_out," .
                " COALESCE(da.weight_in-da.weight_out,0)  weight_sum," .
                ' IFNULL(g.total_weight,0) lm_weight, ' .
                ' COALESCE(da.weight_in - da.weight_out + IFNULL(g.total_Weight,0), 0) sum_total ' .
                " FROM m_material m " .
                " LEFT JOIN " .
                " ( SELECT material_id, sum(weight_in) weight_in,sum(weight_out) weight_out " .
                "   FROM greenearth.t_daily_material" .
                "   WHERE target_date >= '$startDate' AND target_date < '$endDate'" .
                "   GROUP BY material_id) da " .
                "   ON m.material_id = da.material_id " .
                " LEFT JOIN (SELECT material_id,total_weight
                  FROM greenearth.t_getsuji_material " .
                " WHERE yyyymm = '$lastMonth') g " .
                " ON m.material_id = g.material_id " .
                " order by m.material_id ";
        }

        if (strcmp($type, 'crushed') == 0) {
            $strSQL = " SELECT m.material_id, m.material_name,".
                " COALESCE(da.weight_in,0) weight_in,".
                " COALESCE(da.weight_out, 0) weight_out, " .
                " COALESCE(da.weight_in-da.weight_out ,0) weight_sum," .
                " IFNULL(g.lm_weight,0) lm_weight, " .
                " COALESCE(da.weight_in - da.weight_out + IFNULL(g.lm_weight,0), 0) sum_total " .
                " FROM m_material m " .
                " LEFT JOIN " .
                " ( SELECT material_id,sum(weight_in) weight_in, sum(weight_out) weight_out " .
                "   FROM greenearth.t_daily_" . $type .  " " .
                "   WHERE target_date >= '$startDate' AND target_date < '$endDate'" .
                "   GROUP BY material_id) da " .
                "   ON m.material_id = da.material_id " .
                " LEFT JOIN (SELECT material_id, total_weight lm_weight FROM greenearth.t_getsuji_crushed " .
                " WHERE yyyymm = '$lastMonth') g " .
                " ON m.material_id = g.material_id " .
                " order by m.material_id ";
        }

        $datas = DB::select($strSQL);

        // Log::debug($strSQL);
        return $this->success($datas);
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
