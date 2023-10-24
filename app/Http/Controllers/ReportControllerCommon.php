<?php

namespace  App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;

trait ReportControllerCommon
{
    static $ExcelHeaders = [
        "Content-Description" => "File Transfer",
        "Content-Type" => "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
        "Content-Transfer-Encoding" => "binary",
        "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
        "Expires" => "0",
    ];

    static $PdfHeaders = [
        "Content-Type" => "application/pdf",
        "Content-Transfer-Encoding" => "binary",
        "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
        "Expires" => "0",
    ];
}
