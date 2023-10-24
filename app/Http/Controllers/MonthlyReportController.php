<?php

namespace App\Http\Controllers;


use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MonthlyReportController extends Controller
{
    use ReportControllerCommon;

    /** @var MonthlyReportHelper */
    private $monthlyReportHelper;

    public function __construct()
    {
        $this->monthlyReportHelper = new MonthlyReportHelper();
    }

    //product_idによりproduct_nameを取得する

    public function DoExport(Request $request, $target_ym)
    {
        $type = $request->type;

        $target_ids = $request->target_ids;

        $outputFilename = "月次入出庫";

        if ($type == 'product') {
            // $outputFilename = $outputFilename . "($target_ym)-ペレット_" . Carbon::now()->format('-Ymdhis') . '.xlsx';

            $outputFilename = "月次入出庫($target_ym)-ペレット_" . Carbon::now()->format('-YmdHis') . '.xlsx';
            $outputFilePath = Storage::disk("local")->path($outputFilename);

            $this->monthlyReportHelper->exportSheetProduct($target_ym, $target_ids, $outputFilename, $outputFilePath);
        }

        if ($type == 'material') {
            $outputFilename = $outputFilename . "($target_ym)-未粉砕_" . Carbon::now()->format('-YmdHis') . '.xlsx';
            $outputFilePath = Storage::disk("local")->path($outputFilename);
            $this->monthlyReportHelper->exportSheetMaterial($target_ym, $target_ids, $outputFilename, $outputFilePath);
        }

        if ($type == 'crushed') {
            $outputFilename = $outputFilename . "($target_ym)-粉砕済_" . Carbon::now()->format('-YmdHis') . '.xlsx';
            $outputFilePath = Storage::disk("local")->path($outputFilename);
            $this->monthlyReportHelper->exportSheetCrushed($target_ym, $target_ids, $outputFilename, $outputFilePath);
        }


        //  Cookie::queue('downloadId', uniqid(), 5, null, null, false, false);
        // $headers = ['Content-Type: application/pdf'];
        //return response()->download($fileSource, $fileName, $headers);


        // Cookie::queue('downloadId', $downloadId, 5, null, null, false, false);
        return response()->download($outputFilePath, $outputFilename, self::$ExcelHeaders)->deleteFileAfterSend(true);
    }
}
