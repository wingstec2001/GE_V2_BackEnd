<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\lib\SpreadSheet\SpreadSheetUtil;
use Carbon\Carbon;
use App\Models\Product;
use PhpOffice\PhpSpreadsheet\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader; // 拡張子xlsxのExcelファイル読み込み用
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter; // 拡張子xlsxのExcelファイル書き込み用
use PhpOffice\PhpSpreadsheet\Reader\Xls as XlsReader;   // 拡張子xlsのExcelファイル読み込み用
use PhpOffice\PhpSpreadsheet\Writer\Xls as XlsWriter;   // 拡張子xlsのExcelファイル書き込み用
use PhpOffice\PhpSpreadsheet\Spreadsheet;               // スプレッドシート用
use PhpOffice\PhpSpreadsheet\Style\Alignment;           // 出力位置指定用
use PhpOffice\PhpSpreadsheet\Style\Border;              // 罫線用
use PhpOffice\PhpSpreadsheet\Style\Color;               // 色指定用



class MonthlyReportHelper
{
    const COL_WIDTH = 12;   //列のデフォルト幅

    private $backgroundColors = [
        //https://www.wanichan.com/web/resources/color.html
        "FFC0FFC0",  //
        'FFFFC0C0', //COLOR_DARKRED
        'FF00FFFF', //aqua
        'FFFFEBCD', //blanchedalmond
        'FFFF00FF', //fuchsia
        'FFFFA500', //orange
        'FFFFF0F5', //lavenderblush
        'FF7CFC00'  //lawngreen
    ];

    public function getRangeCoordinate($lt_col, $lt_row, $rb_col, $rb_row)
    {
        $lt_colName = Coordinate::stringFromColumnIndex($lt_col);
        $range_from = "$lt_colName$lt_row";

        $rb_colName = Coordinate::stringFromColumnIndex($rb_col);
        $range_to = "$rb_colName$rb_row";

        return "$range_from:$range_to";
    }
    //セルの水平位置を調整する
    public function setCellHorizontal($sheet, $col, $row, $alignment)
    {
        $colName = Coordinate::stringFromColumnIndex($col);
        $coor = "$colName$row";
        $sheet->getStyle($coor)->getAlignment()->setHorizontal($alignment);
        $sheet->getStyle($coor)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    }

    public function setBackgroundColor($sheet, $col, $row, $cnt)
    {
        $coor = $this->getCellCoordinate($col, $row);
        $objFill = $sheet->getStyle($coor)->getFill();
        // 背景のタイプを「塗つぶし」に設定
        $objFill->setFillType(Fill::FILL_SOLID);
        // 背景色を「赤」に設定
        $objFill->getStartColor()->setARGB($this->backgroundColors[$cnt % 8]);
    }

    public function getSumFormula($col_bg, $row_bg, $col_end, $row_end)
    {
        $coor = $this->getCellCoordinate($col_bg, $row_bg);
        $coor = $this->getCellCoordinate($col_bg, 8);
        $formula = "=SUM(" . $coor;
        $coor = $this->getCellCoordinate($col_end, $row_end);
        $formula = $formula . ":" . $coor . ")";

        return $formula;
    }

    private function getProductName($product_id)
    {
        $productName = Product::select('product_name')
            ->where("product_id", $product_id)->get();

        $product_name = "";
        if (count($productName) > 0) {
            $product_name = $productName[0]->product_name;
        }
        return  $product_name;
    }

    private function getMaterialName($material_id)
    {
        $materialName = Material::select('material_name')
            ->where("material_id", $material_id)->get();

        $material_name = "";
        if (count($materialName) > 0) {
            $material_name = $materialName[0]->material_name;
        }
        return  $material_name;
    }


    public function getCellCoordinate($col, $row)
    {
        $colName = Coordinate::stringFromColumnIndex($col);
        return "$colName$row";
    }

    //第１列目を描画する
    private function drawFirstColumns($sheet, $target_ym)
    {

        $start_thismonth = Carbon::createFromFormat('Ymd His', $target_ym . '01 000000');
        $sheet->setCellValue('B2', $start_thismonth->format("Y-m"));

        $end_thismonth =  Carbon::instance($start_thismonth)->addMonth()->addDay(-1);

        $days_thismonth = $end_thismonth->daysInMonth;

        //日付を設定する
        for ($row = 8; $row < 8 + $days_thismonth; $row++) {
            $cell = "A$row";
            $sheet->setCellValue('A' . $row, $start_thismonth->format("Y/m/d"));
            $borders = $sheet->getStyle($cell)->getBorders();
            $borders->getTop()->setBorderStyle(Border::BORDER_THIN);
            $borders->getBottom()->setBorderStyle(Border::BORDER_THIN);

            $start_thismonth->addDay();
        }


        //当月小計行
        $row = $days_thismonth + 8;

        $sheet->setCellValueByColumnAndRow(1, $row, "当月小計");
        $this->setCellHorizontal($sheet, 1, $row, "center");
        $coor = "A$row";
        $sheet->getRowDimension($row)->setRowHeight(35);
        $sheet->getStyle("$coor")->getBorders()->getTop()->setBorderStyle(Border::BORDER_MEDIUM);
        $sheet->getStyle("$coor")->getBorders()->getLeft()->setBorderStyle(Border::BORDER_MEDIUM);
        $sheet->getStyle("$coor")->getBorders()->getRight()->setBorderStyle(Border::BORDER_MEDIUM);
        $sheet->getStyle("$coor")->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle($row)->getFont()->setBold(true);
        $sheet->getStyle($row)->getFont()->setSize(12);


        $row = $row + 1;
        $sheet->setCellValueByColumnAndRow(1, $row, "合　　計");

        $sheet->getRowDimension($row)->setRowHeight(35);
        $this->setCellHorizontal($sheet, 1, $row, "center");
        $coor = "A$row";
        $sheet->getStyle($row)->getFont()->setBold(true);
        $sheet->getStyle($row)->getFont()->setSize(14);
        $sheet->getStyle("$coor")->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle("$coor")->getBorders()->getLeft()->setBorderStyle(Border::BORDER_MEDIUM);
        $sheet->getStyle("$coor")->getBorders()->getRight()->setBorderStyle(Border::BORDER_MEDIUM);
        $sheet->getStyle("$coor")->getBorders()->getBottom()->setBorderStyle(Border::BORDER_MEDIUM);
    }

    public function exportSheet($type, $target_ym, $productIDs,  $outputFilePath)
    {
        $reader = new XlsxReader();

        if ($type == 'product') {
            $templateName = "templates//月次入出庫(ペレット).xlsx";
            $sheetName = 'ペレット';
        };

        if ($type == 'material') {
            $templateName = "templates//月次入出庫(未粉砕).xlsx";
            $sheetName = '未粉砕';
        };

        if ($type == 'crushed') {
            $templateName = "templates//月次入出庫(粉砕済).xlsx";
            $sheetName = '粉砕済';
        };

        $templatePath = resource_path($templateName);
        $spreadsheet = $reader->load($templatePath);
        // $names = $spreadsheet->getSheetNames();


        $sheet = $spreadsheet->setActiveSheetIndexByName($sheetName);

        $this->drawFirstColumns($sheet, $target_ym);

        $startdate_tm = Carbon::createFromFormat('Ymd His', $target_ym . '01 000000');
        $startdate_lm = Carbon::instance($startdate_tm)->addMonth(-1);

        $cnt = 0;
        foreach ($productIDs as $product_id) {
            $this->drawOneProduct($type, $sheet, $cnt, $target_ym, $product_id);
            $cnt += 1;
        }

        //product_id 毎にセル値を設定する

        // ファイル出力
        $writer = new XlsxWriter($spreadsheet);
        $writer->save($outputFilePath);
    }

    // ペレットの出力
    public function exportSheetProduct($target_ym, $productIDs, $outputFilename, $outputFilePath)
    {
        // $outputFilename = "月次入出庫($target_ym)-ペレット_" . Carbon::now()->format('-Ymdhis') . '.xlsx';
        // $outputFilePath = Storage::disk("local")->path($outputFilename);


        $templateName = "templates//月次入出庫(ペレット).xlsx";
        $sheetName = 'ペレット';

        $reader = new XlsxReader();
        $templatePath = resource_path($templateName);
        $spreadsheet = $reader->load($templatePath);
        // $names = $spreadsheet->getSheetNames();



        $sheet = $spreadsheet->setActiveSheetIndexByName($sheetName);

        $this->drawFirstColumns($sheet, $target_ym);

        $startdate_tm = Carbon::createFromFormat('Ymd His', $target_ym . '01 000000');
        $startdate_lm = Carbon::instance($startdate_tm)->addMonth(-1);

        $cnt = 0;
        foreach ($productIDs as $product_id) {
            $this->drawOneProduct($sheet, $cnt, $target_ym, $product_id);
            $cnt += 1;
        }

        //product_id 毎にセル値を設定する

        // ファイル出力
        $writer = new XlsxWriter($spreadsheet);
        $writer->save($outputFilePath);
    }


    public function exportSheetMaterial($target_ym, $targetIDs, $outputFilename, $outputFilePath)
    {
        $templateName = "templates//月次入出庫(未粉砕).xlsx";
        $sheetName = '未粉砕';

        $reader = new XlsxReader();
        $templatePath = resource_path($templateName);
        $spreadsheet = $reader->load($templatePath);


        $sheet = $spreadsheet->setActiveSheetIndexByName($sheetName);

        $this->drawFirstColumns($sheet, $target_ym);

        $startdate_tm = Carbon::createFromFormat('Ymd His', $target_ym . '01 000000');
        $startdate_lm = Carbon::instance($startdate_tm)->addMonth(-1);

        $cnt = 0;
        foreach ($targetIDs as $target_id) {
            $this->drawOneMaterial('material', $sheet, $cnt, $target_ym, $target_id);
            $cnt += 1;
        }

        //product_id 毎にセル値を設定する

        // ファイル出力
        $writer = new XlsxWriter($spreadsheet);
        $writer->save($outputFilePath);
    }


    public function exportSheetCrushed($target_ym, $targetIDs, $outputFilename, $outputFilePath)
    {
        $templateName = "templates//月次入出庫(粉砕済).xlsx";
        $sheetName = '粉砕済';

        $reader = new XlsxReader();
        $templatePath = resource_path($templateName);
        $spreadsheet = $reader->load($templatePath);


        $sheet = $spreadsheet->setActiveSheetIndexByName($sheetName);

        $this->drawFirstColumns($sheet, $target_ym);

        $startdate_tm = Carbon::createFromFormat('Ymd His', $target_ym . '01 000000');
        $startdate_lm = Carbon::instance($startdate_tm)->addMonth(-1);

        $cnt = 0;
        foreach ($targetIDs as $target_id) {
            $this->drawOneMaterial('crushed', $sheet, $cnt, $target_ym, $target_id);
            $cnt += 1;
        }

        //product_id 毎にセル値を設定する

        // ファイル出力
        $writer = new XlsxWriter($spreadsheet);
        $writer->save($outputFilePath);
    }

    public function drawOneProduct($sheet, $cnt, $target_ym, $product_id)
    {

        $product_name = $this->getProductName($product_id);
        //今月の１日
        $startday_tm = Carbon::createFromFormat('Ymd His', $target_ym . '01 000000');
        //前月の１日
        $startday_lm =  Carbon::instance($startday_tm)->addMonth(-1);
        //翌月の1日
        $startday_nm = Carbon::instance($startday_tm)->addMonth();


        $row = 4;
        $col = 2 + $cnt * 4;

        //列の幅を指定する
        for ($i = $col; $i < $col + 4; $i++) {
            $colName = Coordinate::stringFromColumnIndex($i);
            $sheet->getColumnDimension($colName)->setWidth(self::COL_WIDTH);
        }

        //product_idを設定する
        $coor = $this->getRangeCoordinate($col, $row, $col + 3, $row);
        $sheet->mergeCells($coor);
        $sheet->setCellValueByColumnAndRow($col, $row, $product_id);
        $this->setBackgroundColor($sheet, $col, $row, $cnt);
        $this->setCellHorizontal($sheet, $col, $row, "center");
        $sheet->getStyle($coor)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);

        //product_nameを設定する
        $row = $row + 1;
        $coor = $this->getRangeCoordinate($col, $row, $col + 3, $row);
        $sheet->mergeCells($coor);
        $sheet->setCellValueByColumnAndRow($col, $row, $product_name);
        $this->setBackgroundColor($sheet, $col, $row, $cnt);
        $this->setCellHorizontal($sheet, $col, $row, "center");
        // $sheet->getStyle($coor)->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
        // $sheet->getStyle($coor)->getBorders()->getLeft()->setBorderStyle(Border::BORDER_MEDIUM);
        // $sheet->getStyle($coor)->getBorders()->getRight()->setBorderStyle(Border::BORDER_MEDIUM);
        // $sheet->getStyle($coor)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_MEDIUM);
        $coor = $this->getRangeCoordinate($col, $row - 1, $col + 3, $row);


        // $sheet->mergeCellsByColumnAndRow($col, $row, $col + 3, $row);
        // $sheet->setCellValueByColumnAndRow($col, $row, $product_name);
        // $this->setCellHorizontal($sheet, $col, $row, "center");
        //入庫　出庫　当日小計　累計在庫を出力
        $row = 6;
        $titles = ["入庫", "出庫", "当日小計", "累計在庫"];
        $i = 0;
        $col_begin =  $cnt * 4 + 2;
        $col_end = $cnt * 4 + 5;
        for ($col = $col_begin; $col <= $col_end; $col++) {
            // Log::info("col:$col,title:$titles[$i]");
            $sheet->setCellValueByColumnAndRow($col, $row, $titles[$i]);
            $this->setCellHorizontal($sheet, $col, $row, "center");
            $i++;
        }
        //
        $start_thismonth = Carbon::createFromFormat('Ymd His', $target_ym . '01 000000');
        $end_thismonth =  Carbon::instance($start_thismonth)->addMonth()->addDay(-1);
        $days_thismonth = $end_thismonth->daysInMonth;
        $col = 2 + $cnt * 4;
        for ($row = 8; $row < 8 + $days_thismonth; $row++) {
            for ($i = $col; $i < $col + 4; $i++) {
                $colName = Coordinate::stringFromColumnIndex($i);
                $cell = "$colName$row";
                $sheet->getStyle($cell)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            }
        }


        // $coor = $this->getCellCoordinate($col_begin, $row, $col_end, $row);
        // $sheet->getStyle("$coor")->getBorders()->getTop()->setBorderStyle(Border::BORDER_MEDIUM);
        // $sheet->getStyle("$coor")->getBorders()->getRight()->setBorderStyle(Border::BORDER_MEDIUM);

        //前月繰越値を取得する
        $strSQL = " SELECT total_weight" .
            " FROM greenearth.t_getsuji_product " .
            " WHERE yyyymm ='" . $startday_lm->format("Ym") . "'" .
            " AND product_id ='$product_id'";

        $lm_value = DB::select($strSQL);

        $lmw_weight = 0;

        if (count($lm_value) > 0) {

            $lmw_weight =  $lm_value[0]->total_weight;
        }



        // };

        // if ($type == 'material') {
        //     $lmw = $lmw_material;
        // };

        // if ($type == 'crushed') {
        //     $lmw = $lmw_crushed;
        // };

        $col = $cnt * 4 + 2;
        $row = 7;
        //前月繰越 入庫値 (前月累計在庫)
        $sheet->setCellValueByColumnAndRow($col, $row, $lmw_weight);
        $coor = $this->getCellCoordinate($col, $row);
        $sheet->getStyle($coor)->getFont()->setBold(true);
        $sheet->getStyle($coor)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        //前月繰越  出庫値
        $col += 1;
        $sheet->setCellValueByColumnAndRow($col, $row, 0);
        $coor = $this->getCellCoordinate($col, $row);
        $sheet->getStyle($coor)->getFont()->setBold(true);
        $sheet->getStyle($coor)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        //前月繰越  当日小計
        $col += 1;
        $sheet->setCellValueByColumnAndRow($col, $row, 0);
        $coor = $this->getCellCoordinate($col, $row);
        $sheet->getStyle($coor)->getFont()->setBold(true);
        $sheet->getStyle($coor)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        //前月繰越  累計在庫
        $col += 1;
        //$sheet->setCellValueByColumnAndRow($col, $row, $lmw_product);
        $sheet->setCellValueByColumnAndRow($col, $row, "$lmw_weight");
        $coor = $this->getCellCoordinate($col, $row);
        $sheet->getStyle($coor)->getFont()->setBold(true);
        $sheet->getStyle($coor)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        $table = 't_daily_product';
        // if ($type == 'material') {
        //     $table = 't_daily_material';
        // };

        // if ($type == 'crushed') {
        //     $table = 't_daily_crushed';
        // };

        $strSQL = " SELECT target_date, weight_in, weight_out, (weight_in - weight_out) weight_diff " .
            " FROM $table " .
            " WHERE product_id='" . $product_id . "'" .
            " AND target_date>='" . $startday_tm->format("Y-m-d") . "' AND target_date<'" . $startday_nm->format("Y-m-d") . "'" .
            " ORDER BY target_date";

        Log::info($strSQL);

        $day_total =  $lmw_weight;

        $datas = DB::select($strSQL);

        $total_in = 0;
        $total_out = 0;
        $total_diff = 0;

        foreach ($datas as $data) {
            //当日の入庫値
            $weight_in = $data->weight_in;
            $weight_out = $data->weight_out;
            $weight_diff = $data->weight_diff;
            $target_date = Carbon::createFromFormat('Y-m-d', $data->target_date);
            $row = $target_date->day + 7;
            //当日の入庫値
            $col = 2 + $cnt * 4;
            $coor = $this->getCellCoordinate($col, $row, $col + 3, $row);
            $sheet->setCellValueByColumnAndRow($col, $row, $weight_in);
            $coor = $this->getCellCoordinate($col, $row);

            // $sheet->getStyle($coor)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $total_in += $weight_in;

            $formula = "=" . $coor;

            //当日の出庫値
            $col = 3 + $cnt * 4;
            $sheet->setCellValueByColumnAndRow($col, $row, $weight_out);

            $coor = $this->getCellCoordinate($col, $row);
            $sheet->getStyle($coor)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $total_out += $weight_out;
            $formula = $formula . " - " . $coor;

            //当日小計値
            // $day_diff = $weight_in - $weight_out;
            $col = 4 + $cnt * 4;
            //$sheet->setCellValueByColumnAndRow($col, $row, $weight_diff);
            $sheet->setCellValueByColumnAndRow($col, $row, $formula);
            $coor = $this->getCellCoordinate($col, $row);
            $sheet->getStyle($coor)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $total_diff += $weight_diff;

            //当日累計在庫
            $col = 5 + $cnt * 4;
            $coor_above = $this->getCellCoordinate($col, $row - 1);
            $formula = "=$coor_above";
            // $day_total += $weight_diff;
            $coor = $this->getCellCoordinate($col - 1, $row);
            $formula = $formula . " + " . $coor;

            $sheet->setCellValueByColumnAndRow($col, $row, $formula);
            $coor = $this->getCellCoordinate($col, $row);
            $sheet->getStyle($coor)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        }

        //罫線を描画 枠線を描画
        // $col = 2 + $cnt * 4;
        // $col_end = $col_begin + 3;
        $row_begin = 4;
        $row_end = $startday_tm->daysInMonth + 7;
        $coor = $this->getRangeCoordinate($col_begin, $row_begin, $col_end, $row_end);
        $sheet->getStyle($coor)->getBorders()->getTop()->setBorderStyle(Border::BORDER_MEDIUM);
        $sheet->getStyle($coor)->getBorders()->getLeft()->setBorderStyle(Border::BORDER_MEDIUM);
        $sheet->getStyle($coor)->getBorders()->getRight()->setBorderStyle(Border::BORDER_MEDIUM);
        $sheet->getStyle($coor)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_MEDIUM);

        //当月小計 計算式
        $col = $cnt * 4 + 2;
        $formula_in = $this->getSumFormula($col, 8, $col, $row_end);
        $formula_out = $this->getSumFormula($col + 1, 8, $col + 1, $row_end);
        $formula_diff = $this->getSumFormula($col + 2, 8, $col + 2, $row_end);

        //当月小計_入庫合計
        $row = $row_end + 1;
        $sheet->setCellValueByColumnAndRow($col, $row, $formula_in);
        $coor_tm_in = $this->getCellCoordinate($col, $row);
        $sheet->getStyle($coor_tm_in)->getFont()->setBold(true);

        $sheet->getStyle($coor_tm_in)->getBorders()->getRight()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle($coor_tm_in)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);


        //当月小計_出庫合計
        $col = $cnt * 4 + 3;
        $sheet->setCellValueByColumnAndRow($col, $row, $formula_out);
        $coor_tm_out = $this->getCellCoordinate($col, $row);
        $sheet->getStyle($coor_tm_out)->getFont()->setBold(true);
        $sheet->getStyle($coor_tm_out)->getBorders()->getRight()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle($coor_tm_out)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);

        //小計合計
        $col = $cnt * 4 + 4;
        $sheet->setCellValueByColumnAndRow($col, $row, $formula_diff);
        $coor = $this->getCellCoordinate($col, $row);
        $sheet->getStyle($coor)->getFont()->setBold(true);
        $sheet->getStyle($coor)->getBorders()->getRight()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle($coor)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);


        //累計在庫
        $col = $cnt * 4 + 5;
        $coor = $this->getCellCoordinate($col, $row);
        $sheet->getStyle($coor)->getBorders()->getRight()->setBorderStyle(Border::BORDER_MEDIUM);
        $sheet->getStyle($coor)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);

        //合計行
        //入庫=前月繰越_入庫 +入庫_当月小計
        $coor_lm_in = $this->getCellCoordinate($cnt * 4 + 2, 7);
        $coor_lm_out = $this->getCellCoordinate($cnt * 4 + 3, 7);
        $formula_sum_in = "=$coor_lm_in + " .  $coor_tm_in;

        $row = $row_end + 2;
        $col = $cnt * 4 + 2;
        $sheet->setCellValueByColumnAndRow($col, $row, $formula_sum_in);
        $coor_sum_in = $this->getCellCoordinate($col, $row);
        $sheet->getStyle($coor_sum_in)->getFont()->setBold(true);
        $sheet->getStyle($coor_sum_in)->getFont()->setSize(14);
        $sheet->getStyle($coor_sum_in)->getBorders()->getRight()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle($coor_sum_in)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_MEDIUM);
        $colName = Coordinate::stringFromColumnIndex($col);
        $colWidth = $sheet->getColumnDimension($colName)->getWidth();
        if ($colWidth > self::COL_WIDTH) {
            $sheet->getColumnDimension($colName)->setAutoSize(true);
        } else {
            $sheet->getColumnDimension($colName)->setWidth(self::COL_WIDTH);
        }


        //出庫=前月繰越_出庫 +出庫_当月小計
        $coor_lm_out = $this->getCellCoordinate($cnt * 4 + 3, 7);
        $formula_sum_out = "=$coor_lm_out + " .  $coor_tm_out;

        $row = $row_end + 2;
        $col = $cnt * 4 + 3;
        $sheet->setCellValueByColumnAndRow($col, $row, $formula_sum_out);
        $coor_sum_out = $this->getCellCoordinate($col, $row);
        $sheet->getStyle($coor_sum_out)->getFont()->setBold(true);
        $sheet->getStyle($coor_sum_out)->getFont()->setSize(14);

        $sheet->getStyle($coor_sum_out)->getBorders()->getRight()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle($coor_sum_out)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_MEDIUM);
        $colName = Coordinate::stringFromColumnIndex($col);
        $colWidth = $sheet->getColumnDimension($colName)->getWidth();
        if ($colWidth > self::COL_WIDTH) {
            $sheet->getColumnDimension($colName)->setAutoSize(true);
        } else {
            $sheet->getColumnDimension($colName)->setWidth(self::COL_WIDTH);
        }

        //合計行当月小計分、枠線を描画
        $col = $cnt * 4 + 4;
        $coor = $this->getCellCoordinate($col, $row);
        $sheet->getStyle($coor)->getBorders()->getRight()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle($coor)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_MEDIUM);
        $colName = Coordinate::stringFromColumnIndex($col);
        $colWidth = $sheet->getColumnDimension($colName)->getWidth();
        if ($colWidth > self::COL_WIDTH) {
            $sheet->getColumnDimension($colName)->setAutoSize(true);
        } else {
            $sheet->getColumnDimension($colName)->setWidth(self::COL_WIDTH);
        }
        //合計行　累計在庫
        $col = $cnt * 4 + 5;
        $coor = $this->getCellCoordinate($col, $row);
        $formula = "= $coor_sum_in - $coor_sum_out";
        $sheet->setCellValueByColumnAndRow($col, $row, $formula);

        $sheet->getStyle($coor)->getFont()->setBold(true);
        $sheet->getStyle($coor)->getFont()->setSize(14);
        $sheet->getStyle($coor)->getBorders()->getRight()->setBorderStyle(Border::BORDER_MEDIUM);
        $sheet->getStyle($coor)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_MEDIUM);
        $colName = Coordinate::stringFromColumnIndex($col);
        $colWidth = $sheet->getColumnDimension($colName)->getWidth();
        if ($colWidth > self::COL_WIDTH) {
            $sheet->getColumnDimension($colName)->setAutoSize(true);
        } else {
            $sheet->getColumnDimension($colName)->setWidth(self::COL_WIDTH);
        }
    }


    public function drawOneMaterial($type, $sheet, $cnt, $target_ym, $material_id)
    {

        $material_name = $this->getProductName($material_id);
        //今月の１日
        $startday_tm = Carbon::createFromFormat('Ymd His', $target_ym . '01 000000');
        //前月の１日
        $startday_lm =  Carbon::instance($startday_tm)->addMonth(-1);
        //翌月の1日
        $startday_nm = Carbon::instance($startday_tm)->addMonth();


        $row = 4;
        $col = 2 + $cnt * 4;

        //列の幅を指定する
        for ($i = $col; $i < $col + 4; $i++) {
            $colName = Coordinate::stringFromColumnIndex($i);
            $sheet->getColumnDimension($colName)->setWidth(self::COL_WIDTH);
        }

        //product_idを設定する
        $coor = $this->getRangeCoordinate($col, $row, $col + 3, $row);
        $sheet->mergeCells($coor);
        $sheet->setCellValueByColumnAndRow($col, $row, $material_id);
        $this->setBackgroundColor($sheet, $col, $row, $cnt);
        $this->setCellHorizontal($sheet, $col, $row, "center");
        $sheet->getStyle($coor)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);

        //product_nameを設定する
        $row = $row + 1;
        $coor = $this->getRangeCoordinate($col, $row, $col + 3, $row);
        $sheet->mergeCells($coor);
        $sheet->setCellValueByColumnAndRow($col, $row, $material_name);
        $this->setBackgroundColor($sheet, $col, $row, $cnt);
        $this->setCellHorizontal($sheet, $col, $row, "center");
        // $sheet->getStyle($coor)->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
        // $sheet->getStyle($coor)->getBorders()->getLeft()->setBorderStyle(Border::BORDER_MEDIUM);
        // $sheet->getStyle($coor)->getBorders()->getRight()->setBorderStyle(Border::BORDER_MEDIUM);
        // $sheet->getStyle($coor)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_MEDIUM);
        $coor = $this->getRangeCoordinate($col, $row - 1, $col + 3, $row);


        // $sheet->mergeCellsByColumnAndRow($col, $row, $col + 3, $row);
        // $sheet->setCellValueByColumnAndRow($col, $row, $product_name);
        // $this->setCellHorizontal($sheet, $col, $row, "center");
        //入庫　出庫　当日小計　累計在庫を出力
        $row = 6;
        $titles = ["入庫", "出庫", "当日小計", "累計在庫"];
        $i = 0;
        $col_begin =  $cnt * 4 + 2;
        $col_end = $cnt * 4 + 5;
        for ($col = $col_begin; $col <= $col_end; $col++) {
            // Log::info("col:$col,title:$titles[$i]");
            $sheet->setCellValueByColumnAndRow($col, $row, $titles[$i]);
            $this->setCellHorizontal($sheet, $col, $row, "center");
            $i++;
        }
        //
        $start_thismonth = Carbon::createFromFormat('Ymd His', $target_ym . '01 000000');
        $end_thismonth =  Carbon::instance($start_thismonth)->addMonth()->addDay(-1);
        $days_thismonth = $end_thismonth->daysInMonth;
        $col = 2 + $cnt * 4;
        for ($row = 8; $row < 8 + $days_thismonth; $row++) {
            for ($i = $col; $i < $col + 4; $i++) {
                $colName = Coordinate::stringFromColumnIndex($i);
                $cell = "$colName$row";
                $sheet->getStyle($cell)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            }
        }

        $table = 't_getsuji_crushed';
        if ($type == 'material') {
            $table = 't_getsuji_material';
        }


        //前月繰越値を取得する
        $strSQL = " SELECT total_weight" .
            " FROM $table " .
            " WHERE yyyymm ='" . $startday_lm->format("Ym") . "'" .
            " AND material_id ='$material_id'";

        $lm_value = DB::select($strSQL);

        $lmw_weight = 0;

        if (count($lm_value) > 0) {
            $lmw_weight =  $lm_value[0]->total_weight;
        }

        $col = $cnt * 4 + 2;
        $row = 7;
        //前月繰越 入庫値 (前月累計在庫)
        $sheet->setCellValueByColumnAndRow($col, $row, $lmw_weight);
        $coor = $this->getCellCoordinate($col, $row);
        $sheet->getStyle($coor)->getFont()->setBold(true);
        $sheet->getStyle($coor)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        //前月繰越  出庫値
        $col += 1;
        $sheet->setCellValueByColumnAndRow($col, $row, 0);
        $coor = $this->getCellCoordinate($col, $row);
        $sheet->getStyle($coor)->getFont()->setBold(true);
        $sheet->getStyle($coor)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        //前月繰越  当日小計
        $col += 1;
        $sheet->setCellValueByColumnAndRow($col, $row, 0);
        $coor = $this->getCellCoordinate($col, $row);
        $sheet->getStyle($coor)->getFont()->setBold(true);
        $sheet->getStyle($coor)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        //前月繰越  累計在庫
        $col += 1;
        //$sheet->setCellValueByColumnAndRow($col, $row, $lmw_product);
        $sheet->setCellValueByColumnAndRow($col, $row, "$lmw_weight");
        $coor = $this->getCellCoordinate($col, $row);
        $sheet->getStyle($coor)->getFont()->setBold(true);
        $sheet->getStyle($coor)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);


        $table = 't_daily_material';
        if ($type == 'crushed') {
            $table = 't_daily_crushed';
        };

        $strSQL = " SELECT target_date, weight_in, weight_out, (weight_in - weight_out) weight_diff " .
            " FROM $table " .
            " WHERE material_id='" . $material_id . "'" .
            " AND target_date>='" . $startday_tm->format("Y-m-d") . "' AND target_date<'" . $startday_nm->format("Y-m-d") . "'" .
            " ORDER BY target_date";

        // Log::info($strSQL);

        $day_total =  $lmw_weight;

        $datas = DB::select($strSQL);

        $total_in = 0;
        $total_out = 0;
        $total_diff = 0;

        foreach ($datas as $data) {
            //当日の入庫値
            $weight_in = $data->weight_in;
            $weight_out = $data->weight_out;
            $weight_diff = $data->weight_diff;
            $target_date = Carbon::createFromFormat('Y-m-d', $data->target_date);
            $row = $target_date->day + 7;
            //当日の入庫値
            $col = 2 + $cnt * 4;
            $coor = $this->getCellCoordinate($col, $row, $col + 3, $row);
            $sheet->setCellValueByColumnAndRow($col, $row, $weight_in);
            $coor = $this->getCellCoordinate($col, $row);

            // $sheet->getStyle($coor)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $total_in += $weight_in;

            $formula = "=" . $coor;

            //当日の出庫値
            $col = 3 + $cnt * 4;
            $sheet->setCellValueByColumnAndRow($col, $row, $weight_out);

            $coor = $this->getCellCoordinate($col, $row);
            $sheet->getStyle($coor)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $total_out += $weight_out;
            $formula = $formula . " - " . $coor;

            //当日小計値
            // $day_diff = $weight_in - $weight_out;
            $col = 4 + $cnt * 4;
            //$sheet->setCellValueByColumnAndRow($col, $row, $weight_diff);
            $sheet->setCellValueByColumnAndRow($col, $row, $formula);
            $coor = $this->getCellCoordinate($col, $row);
            $sheet->getStyle($coor)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $total_diff += $weight_diff;

            //当日累計在庫
            $col = 5 + $cnt * 4;
            $coor_above = $this->getCellCoordinate($col, $row - 1);
            $formula = "=$coor_above";
            // $day_total += $weight_diff;
            $coor = $this->getCellCoordinate($col - 1, $row);
            $formula = $formula . " + " . $coor;

            $sheet->setCellValueByColumnAndRow($col, $row, $formula);
            $coor = $this->getCellCoordinate($col, $row);
            $sheet->getStyle($coor)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        }

        //罫線を描画 枠線を描画
        // $col = 2 + $cnt * 4;
        // $col_end = $col_begin + 3;
        $row_begin = 4;
        $row_end = $startday_tm->daysInMonth + 7;
        $coor = $this->getRangeCoordinate($col_begin, $row_begin, $col_end, $row_end);
        $sheet->getStyle($coor)->getBorders()->getTop()->setBorderStyle(Border::BORDER_MEDIUM);
        $sheet->getStyle($coor)->getBorders()->getLeft()->setBorderStyle(Border::BORDER_MEDIUM);
        $sheet->getStyle($coor)->getBorders()->getRight()->setBorderStyle(Border::BORDER_MEDIUM);
        $sheet->getStyle($coor)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_MEDIUM);

        //当月小計 計算式
        $col = $cnt * 4 + 2;
        $formula_in = $this->getSumFormula($col, 8, $col, $row_end);
        $formula_out = $this->getSumFormula($col + 1, 8, $col + 1, $row_end);
        $formula_diff = $this->getSumFormula($col + 2, 8, $col + 2, $row_end);

        //当月小計_入庫合計
        $row = $row_end + 1;
        $sheet->setCellValueByColumnAndRow($col, $row, $formula_in);
        $coor_tm_in = $this->getCellCoordinate($col, $row);
        $sheet->getStyle($coor_tm_in)->getFont()->setBold(true);

        $sheet->getStyle($coor_tm_in)->getBorders()->getRight()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle($coor_tm_in)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);


        //当月小計_出庫合計
        $col = $cnt * 4 + 3;
        $sheet->setCellValueByColumnAndRow($col, $row, $formula_out);
        $coor_tm_out = $this->getCellCoordinate($col, $row);
        $sheet->getStyle($coor_tm_out)->getFont()->setBold(true);
        $sheet->getStyle($coor_tm_out)->getBorders()->getRight()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle($coor_tm_out)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);

        //小計合計
        $col = $cnt * 4 + 4;
        $sheet->setCellValueByColumnAndRow($col, $row, $formula_diff);
        $coor = $this->getCellCoordinate($col, $row);
        $sheet->getStyle($coor)->getFont()->setBold(true);
        $sheet->getStyle($coor)->getBorders()->getRight()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle($coor)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);


        //累計在庫
        $col = $cnt * 4 + 5;
        $coor = $this->getCellCoordinate($col, $row);
        $sheet->getStyle($coor)->getBorders()->getRight()->setBorderStyle(Border::BORDER_MEDIUM);
        $sheet->getStyle($coor)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);

        //合計行
        //入庫=前月繰越_入庫 +入庫_当月小計
        $coor_lm_in = $this->getCellCoordinate($cnt * 4 + 2, 7);
        $coor_lm_out = $this->getCellCoordinate($cnt * 4 + 3, 7);
        $formula_sum_in = "=$coor_lm_in + " .  $coor_tm_in;

        $row = $row_end + 2;
        $col = $cnt * 4 + 2;
        $sheet->setCellValueByColumnAndRow($col, $row, $formula_sum_in);
        $coor_sum_in = $this->getCellCoordinate($col, $row);
        $sheet->getStyle($coor_sum_in)->getFont()->setBold(true);
        $sheet->getStyle($coor_sum_in)->getFont()->setSize(14);
        $sheet->getStyle($coor_sum_in)->getBorders()->getRight()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle($coor_sum_in)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_MEDIUM);
        $colName = Coordinate::stringFromColumnIndex($col);
        $colWidth = $sheet->getColumnDimension($colName)->getWidth();
        if ($colWidth > self::COL_WIDTH) {
            $sheet->getColumnDimension($colName)->setAutoSize(true);
        } else {
            $sheet->getColumnDimension($colName)->setWidth(self::COL_WIDTH);
        }


        //出庫=前月繰越_出庫 +出庫_当月小計
        $coor_lm_out = $this->getCellCoordinate($cnt * 4 + 3, 7);
        $formula_sum_out = "=$coor_lm_out + " .  $coor_tm_out;

        $row = $row_end + 2;
        $col = $cnt * 4 + 3;
        $sheet->setCellValueByColumnAndRow($col, $row, $formula_sum_out);
        $coor_sum_out = $this->getCellCoordinate($col, $row);
        $sheet->getStyle($coor_sum_out)->getFont()->setBold(true);
        $sheet->getStyle($coor_sum_out)->getFont()->setSize(14);

        $sheet->getStyle($coor_sum_out)->getBorders()->getRight()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle($coor_sum_out)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_MEDIUM);
        $colName = Coordinate::stringFromColumnIndex($col);
        $colWidth = $sheet->getColumnDimension($colName)->getWidth();
        if ($colWidth > self::COL_WIDTH) {
            $sheet->getColumnDimension($colName)->setAutoSize(true);
        } else {
            $sheet->getColumnDimension($colName)->setWidth(self::COL_WIDTH);
        }

        //合計行当月小計分、枠線を描画
        $col = $cnt * 4 + 4;
        $coor = $this->getCellCoordinate($col, $row);
        $sheet->getStyle($coor)->getBorders()->getRight()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle($coor)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_MEDIUM);
        $colName = Coordinate::stringFromColumnIndex($col);
        $colWidth = $sheet->getColumnDimension($colName)->getWidth();
        if ($colWidth > self::COL_WIDTH) {
            $sheet->getColumnDimension($colName)->setAutoSize(true);
        } else {
            $sheet->getColumnDimension($colName)->setWidth(self::COL_WIDTH);
        }
        //合計行　累計在庫
        $col = $cnt * 4 + 5;
        $coor = $this->getCellCoordinate($col, $row);
        $formula = "= $coor_sum_in - $coor_sum_out";
        $sheet->setCellValueByColumnAndRow($col, $row, $formula);

        $sheet->getStyle($coor)->getFont()->setBold(true);
        $sheet->getStyle($coor)->getFont()->setSize(14);
        $sheet->getStyle($coor)->getBorders()->getRight()->setBorderStyle(Border::BORDER_MEDIUM);
        $sheet->getStyle($coor)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_MEDIUM);
        $colName = Coordinate::stringFromColumnIndex($col);
        $colWidth = $sheet->getColumnDimension($colName)->getWidth();
        if ($colWidth > self::COL_WIDTH) {
            $sheet->getColumnDimension($colName)->setAutoSize(true);
        } else {
            $sheet->getColumnDimension($colName)->setWidth(self::COL_WIDTH);
        }
    }
}
