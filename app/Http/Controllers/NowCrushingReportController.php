<?php

namespace App\Http\Controllers;


use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class NowCrushingReportController extends Controller
{
    use ReportControllerCommon;

    /** @var NowCrushingReportHelper */
    private $nowCrushingReportHelper;

    public function __construct()
    {
        $this->nowCrushingReportHelper = new NowCrushingReportHelper();
    }

    //product_idによりproduct_nameを取得する

    public function CDoExport(Request $request)
    {
        $outputFilename = '当月在庫_粉砕済_'  . Carbon::now()->format('Ymd') . '.xlsx';
        $outputFilePath = Storage::disk("local")->path($outputFilename);
        $this->nowCrushingReportHelper->exportSheet($outputFilePath);
        return response()->download($outputFilePath, $outputFilename, self::$ExcelHeaders)->deleteFileAfterSend(true);
    }
}
