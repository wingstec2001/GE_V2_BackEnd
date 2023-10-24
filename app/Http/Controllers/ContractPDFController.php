<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use PDF;

class ContractPDFController extends Controller
{
    //
    public function Index()
    {
        $data = [
            'buyer' => '上海万达塑料机械公司',
            'contract_product' => 'ABS-PP 黒25400kg',
            'contract_amount' => '200,000',
            'contract_date' => '2022/02/24',
        ];
        $pdf = PDF::loadView('contract', $data);
        $path = storage_path('contract.pdf');
        $pdf->save($path);
        //Storage::put('contract.pdf', $pdf->output());
        //return $pdf->download("contract.pdf");
    }
}
