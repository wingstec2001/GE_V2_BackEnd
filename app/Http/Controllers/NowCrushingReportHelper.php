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



class NowCrushingReportHelper
{
    const COL_WIDTH = 12;   //列のデフォルト幅
    const FIRST_ROW = 3;    //起始行の値

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
        $objFill->getStartColor()->setARGB('FFC0FFC0');
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


    public function getCellCoordinate($col, $row)
    {
        $colName = Coordinate::stringFromColumnIndex($col);
        return "$colName$row";
    }

    public function exportSheet($outputFilePath)
    {
        $reader = new XlsxReader();
        $templateName = "templates//粉砕済当月在庫.xlsx";

        $templatePath = resource_path($templateName);
        $spreadsheet = $reader->load($templatePath);

        $sheet = $spreadsheet->setActiveSheetIndex(0);
        $this->drawFirstColumns($sheet);

        $cnt = 0;
        $products = Product::select('product_id', 'product_name')->orderBy('product_id', 'asc')->pluck('product_name', 'product_id');
        // $productNames = [];
        // foreach($products as $product){
        //     $productNames[$product->product_id] = $product->product_name;
        // }
        $row = self::FIRST_ROW;
        foreach ($products as $product_id => $product_name) {
            $this->drawOneProduct($sheet, $cnt, $product_id, $product_name, $row);
            $cnt += 1;
        }


        // ファイル出力
        $writer = new XlsxWriter($spreadsheet);
        $writer->save($outputFilePath);
    }

    private function drawFirstColumns($sheet)
    {
        $now = Carbon::now()->format('Y/m/d');
        $sheet->setCellValue('B2', $now);
    }

    public function drawOneProduct($sheet, $cnt, $product_id, $product_name, &$row)
    {
        $col = 1;
        $row += 1;

        //product_idを設定する
        $sheet->setCellValueByColumnAndRow($col, $row, $product_id);
        $this->setBackgroundColor($sheet, $col, $row, $cnt);
        $this->setCellHorizontal($sheet, $col, $row, "center");

        //product_nameを設定する
        $coor = $this->getRangeCoordinate(2, $row, 4, $row);
        $sheet->mergeCells($coor);
        $sheet->setCellValueByColumnAndRow(2, $row, $product_name);
        $this->setBackgroundColor($sheet, 2, $row, $cnt);
        $this->setCellHorizontal($sheet, 2, $row, "center");
        $sheet->getStyle($coor)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);


        //入庫　出庫　当日小計　累計在庫を出力
        $row += 1;
        $titles = ["No", "重量(kg)", "フレコン数", "累計在庫"];

        for ($col = 1; $col <= 4; $col++) {
            $sheet->setCellValueByColumnAndRow($col, $row, $titles[$col - 1])->getRowDimension($row)->setRowHeight(26);
            $this->setCellHorizontal($sheet, $col, $row, "center");
        }

        $strSQL = " SELECT crushed_weight, fcb, t_weight" .
            " FROM greenearth.v_stock_crushed" .
            " WHERE product_id='" . $product_id . "'";

        $datas = DB::select($strSQL);

        $no = 1;
        $row_begin = $row + 1;
        foreach ($datas as $data) {
            $row += 1;
            $crushed_weight = $data->crushed_weight;
            $fcb = $data->fcb;
            $t_weight = $data->t_weight;
            //No. 
            $col = 1;
            $sheet->setCellValueByColumnAndRow($col, $row, $no);
            $coor = $this->getCellCoordinate($col, $row);
            $sheet->getStyle($coor)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $this->setCellHorizontal($sheet, $col, $row, "center");

            //重量
            $col = 2;
            $sheet->setCellValueByColumnAndRow($col, $row, $crushed_weight);
            $coor = $this->getCellCoordinate($col, $row);
            $sheet->getStyle($coor)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);


            //フレコン数
            $col = 3;
            $sheet->setCellValueByColumnAndRow($col, $row, $fcb);
            $coor = $this->getCellCoordinate($col, $row);
            $sheet->getStyle($coor)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);


            //累計在庫
            $col = 4;
            $sheet->setCellValueByColumnAndRow($col, $row, $t_weight);
            $coor = $this->getCellCoordinate($col, $row);
            $sheet->getStyle($coor)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $no += 1;
        }
        $row_end = $row;

        //合計行
        $row = $row + 1;
        $coor = $this->getCellCoordinate(1, $row);
        $sheet->setCellValueByColumnAndRow(1, $row, "合計")->getStyle($coor)->getFont()->setBold(true)->setName('游ゴシック')
            ->setSize(11);
        $this->setCellHorizontal($sheet, 1, $row, "center");

        //合計値
        if ($row_end >= $row_begin) {
            $coor_total = $this->getRangeCoordinate(4, $row_begin, 4, $row_end);
            $formula_sum = "=sum($coor_total)";
        } else {
            $formula_sum = "0";
        }

        $coor = $this->getCellCoordinate(4, $row);
        $sheet->setCellValueByColumnAndRow(4, $row, $formula_sum)->getStyle($coor)->getFont()->setBold(true)->setName('游ゴシック')
            ->setSize(11);

        $coor = $this->getRangeCoordinate(1, $row, 4, $row);
        $sheet->getStyle($coor)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        $row = $row + 1;
    }
}
