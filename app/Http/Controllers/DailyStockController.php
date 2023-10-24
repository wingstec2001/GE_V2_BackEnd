<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\DailyCrushed;
use App\Models\DailyMaterial;
use App\Models\DailyProduct;
use App\Models\GetsujiCrushed;
use App\Models\GetsujiInfo;
use App\Models\GetsujiMaterial;
use Illuminate\Support\Carbon;
use App\Services\DailyRecalculateService;

class DailyStockController extends Controller
{
    /**
     * Display a listing of the t_daily_product.
     *
     */

    protected $dailyRecalculateService;

    public function __construct(DailyRecalculateService $dailyRecalculateService)
    {
        $this->dailyRecalculateService = $dailyRecalculateService;
    }

    public function GetProdSt(Request $request)
    {
        $target_ym = $request['month'];
        $startdate_tm = Carbon::createFromFormat('Ymd His', $target_ym . '01 000000');
        $startdate_lm = Carbon::instance($startdate_tm)->addMonth(-1);
        $startDate = $startdate_tm->startOfMonth()->format('Y-m-d');
        $endDate = $startdate_tm->endOfMonth()->format('Y-m-d');
        $lastMonth = $startdate_lm->format('Ym');
        $product_id = $request['product_id'];
        $product_stock = DailyProduct::where('product_id', $product_id)
            ->whereBetween('target_date', [$startDate, $endDate])
            ->orderby('target_date', 'asc')
            ->get();

        //前月繰越値を取得する
        $strSQL =
            ' SELECT total_weight, id FROM greenearth.t_getsuji_product' .
            " WHERE yyyymm ='$lastMonth' " .
            " AND product_id ='$product_id'";
        $lmw_weight = 0;
        $lm_value = DB::select($strSQL);
        $getsuji_id = 0;
        if (count($lm_value) > 0) {
            $lmw_weight = $lm_value[0]->total_weight;
            $getsuji_id = $lm_value[0]->id;
        }

        $result = collect([
            'dly_prodSt' => $product_stock,
            'wgtin_lmSum' => $lmw_weight,
            'wgtout_lmSum' => 0,
            'net_weight' => $lmw_weight,
            'getsuji_id' => $getsuji_id,
        ]);

        return $this->success($result);
    }

    //月次入出庫日毎 材料取得
    public function GetMatSt(Request $request)
    {
        $target_ym = $request['month'];
        $startdate_tm = Carbon::createFromFormat('Ymd His', $target_ym . '01 000000');
        $startdate_lm = Carbon::instance($startdate_tm)->addMonth(-1);
        $startDate = $startdate_tm->startOfMonth()->format('Y-m-d');
        $endDate = $startdate_tm->endOfMonth()->format('Y-m-d');

        $lastMonth = $startdate_lm->format('Ym');
        $material_id = $request['material_id'];

        //前月繰越値を取得する
        $strSQL =
            ' SELECT total_weight, id FROM greenearth.t_getsuji_material' .
            " WHERE yyyymm ='$lastMonth' " .
            " AND material_id ='$material_id'";
        $lmw_weight = 0;
        $getsuji_id = 0;
        $lm_value = DB::select($strSQL);
        if (count($lm_value) > 0) {
            $lmw_weight = $lm_value[0]->total_weight;
            $getsuji_id = $lm_value[0]->id;
        }

        $material_stock = DailyMaterial::where('material_id', $material_id)
            ->whereBetween('target_date', [$startDate, $endDate])
            ->orderby('target_date', 'asc')
            ->get();

        $result = collect([
            'dly_matSt' => $material_stock,
            'wgtin_lmSum' => $lmw_weight,
            'wgtout_lmSum' => 0,
            'net_weight' => $lmw_weight,
            'getsuji_id' => $getsuji_id,
        ]);

        return $this->success($result);
    }

    //月次入出庫 材料在庫再計算
    public function RecalculateMaterial(Request $request)
    {
        $target_ym = $request['month'];
        $startdate_tm = Carbon::createFromFormat('Ymd His', $target_ym . '01 000000');
        $startdate_lm = Carbon::instance($startdate_tm)->addMonth(-1);
        $startDate = $startdate_tm->startOfMonth()->format('Y-m-d');
        $endDate = $startdate_tm->endOfMonth()->format('Y-m-d');

        $thisMonth = $startdate_tm->format('Ym');
        $lastMonth = $startdate_lm->format('Ym');
        $material_id = $request['material_id'];

        //前月繰越値を取得する
        $strSQL =
            ' SELECT total_weight, id FROM greenearth.t_getsuji_material' .
            " WHERE yyyymm ='$lastMonth' " .
            " AND material_id ='$material_id'";
        $lm_weight = 0;
        $getsuji_id = 0;
        $lm_value = DB::select($strSQL);
        if (count($lm_value) > 0) {
            $lm_weight = $lm_value[0]->total_weight;
            $getsuji_id = $lm_value[0]->id;
        }

        $this->dailyRecalculateService->RecalculateMaterial($material_id, $target_ym, $lm_weight);

        $material_stock = DailyMaterial::where('material_id', $material_id)
            ->whereBetween('target_date', [$startDate, $endDate])
            ->orderby('target_date', 'asc')
            ->get();

        //当月分の日次在庫を再計算、
        $result = collect([
            'dly_matSt' => $material_stock,
            'wgtin_lmSum' => $lm_weight,
            'wgtout_lmSum' => 0,
            'net_weight' => $lm_weight,
            'getsuji_id' => $getsuji_id,
        ]);

        return $this->success($result);
    }

    //月次入出庫日毎 粉砕済取得
    public function GetCruSt(Request $request)
    {
        $target_ym = $request['month'];
        $startdate_tm = Carbon::createFromFormat('Ymd His', $target_ym . '01 000000');
        $startdate_lm = Carbon::instance($startdate_tm)->addMonth(-1);
        $startDate = $startdate_tm->startOfMonth()->format('Y-m-d');
        $endDate = $startdate_tm->endOfMonth()->format('Y-m-d');
        $lastMonth = $startdate_lm->format('Ym');

        $material_id = $request['material_id'];
        $crushed_stock = DailyCrushed::where('material_id', $material_id)
            ->whereBetween('target_date', [$startDate, $endDate])
            ->orderby('target_date', 'asc')
            ->get();

        //前月繰越値を取得する
        $strSQL =
            ' SELECT total_weight, id FROM greenearth.t_getsuji_crushed' .
            " WHERE yyyymm ='$lastMonth' " .
            " AND material_id ='$material_id'";
        $lmw_weight = 0;
        $getsuji_id = 0;
        $lm_value = DB::select($strSQL);
        if (count($lm_value) > 0) {
            $lmw_weight = $lm_value[0]->total_weight;
            $getsuji_id = $lm_value[0]->id;
        }

        $result = collect([
            'dly_cruSt' => $crushed_stock,
            'wgtin_lmSum' => $lmw_weight,
            'wgtout_lmSum' => 0,
            'net_weight' => $lmw_weight,
            'getsuji_id' => $getsuji_id,
        ]);

        return $this->success($result);
    }

    // 粉砕済再計算
    public function RecalculateCrushed(Request $request)
    {
        $target_ym = $request['month'];
        $startdate_tm = Carbon::createFromFormat('Ymd His', $target_ym . '01 000000');
        $startdate_lm = Carbon::instance($startdate_tm)->addMonth(-1);
        $startDate = $startdate_tm->startOfMonth()->format('Y-m-d');
        $endDate = $startdate_tm->endOfMonth()->format('Y-m-d');
        $lastMonth = $startdate_lm->format('Ym');

        //前月繰越値を取得する
        $material_id = $request['material_id'];
        $strSQL =
            ' SELECT total_weight, id FROM greenearth.t_getsuji_crushed' .
            " WHERE yyyymm ='$lastMonth' " .
            " AND material_id ='$material_id'";

        $lm_weight = 0;
        $getsuji_id = 0;
        $lm_value = DB::select($strSQL);
        if (count($lm_value) > 0) {
            $lm_weight = $lm_value[0]->total_weight;
            $getsuji_id = $lm_value[0]->id;
        }

        $this->dailyRecalculateService->RecalculateCrushed($material_id, $target_ym, $lm_weight);

        $crushed_stock = DailyCrushed::where('material_id', $material_id)
            ->whereBetween('target_date', [$startDate, $endDate])
            ->orderby('target_date', 'asc')
            ->get();

        $result = collect([
            'dly_cruSt' => $crushed_stock,
            'wgtin_lmSum' => $lm_weight,
            'wgtout_lmSum' => 0,
            'net_weight' => $lm_weight,
            'getsuji_id' => $getsuji_id,
        ]);

        return $this->success($result);
    }

    public function RecalculatePellet(Request $request)
    {
        $target_ym = $request['month'];
        $startdate_tm = Carbon::createFromFormat('Ymd His', $target_ym . '01 000000');
        $startdate_lm = Carbon::instance($startdate_tm)->addMonth(-1);
        $startDate = $startdate_tm->startOfMonth()->format('Y-m-d');
        $endDate = $startdate_tm->endOfMonth()->format('Y-m-d');
        $lastMonth = $startdate_lm->format('Ym');
        $product_id = $request['product_id'];

        //前月繰越値を取得する
        $strSQL =
            ' SELECT total_weight, id FROM greenearth.t_getsuji_product' .
            " WHERE yyyymm ='$lastMonth' " .
            " AND product_id ='$product_id'";
        $lm_weight = 0;
        $lm_value = DB::select($strSQL);
        $getsuji_id = 0;
        if (count($lm_value) > 0) {
            $lm_weight = $lm_value[0]->total_weight;
            $getsuji_id = $lm_value[0]->id;
        }

        $this->dailyRecalculateService->RecalculatePellet($product_id, $target_ym, $lm_weight);

        $product_stock = DailyProduct::where('product_id', $product_id)
            ->whereBetween('target_date', [$startDate, $endDate])
            ->orderby('target_date', 'asc')
            ->get();

        $result = collect([
            'dly_prodSt' => $product_stock,
            'wgtin_lmSum' => $lm_weight,
            'wgtout_lmSum' => 0,
            'net_weight' => $lm_weight,
            'getsuji_id' => $getsuji_id,
        ]);

        return $this->success($result);
    }
}
