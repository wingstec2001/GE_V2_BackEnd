<?php

namespace App\Http\Requests\Api;

use Illuminate\Support\Facades\Log;

class BidRequest extends FormRequest
{

    public function authorize()
    {
        //false代表权限验证不通过，返回403错误
        //true代表权限认证通过
        return true;
    }
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {

        switch ($this->method()) {
            case 'GET': 
                //Detail
                $id = $this->route('id');
                error_log('--------------->GET Request valid ' . $id . ' Some message here.'); {
                    return [
                        'id' => 'required|exists:m_bid'
                    ];
                }
            case 'POST':
                //Add
                {
                    return [
                        'bid_id' => 'required|unique:m_bid',
                        'bid_name' => 'required',
                        'product_id' => 'required',
                        'bid_open_dt' => 'required',
                        'bid_comp_dt' => 'required',
                        'bid_weight' => 'required',
                        'bid_min_price' => 'required',
                        'bid_max_c_cnt' => 'required',
                    ];
                }
            case 'PUT': 
                //Update
                return [
                    'id' => 'required|exists:m_bid',
                    // 'product_img' => 'mimes:jpeg,bmp,png,gif',
                ];
            case 'PATCH':
            case 'DELETE': 
                //Delete
                return [
                    'id' => 'required|exists:m_bid'
                ];
            default: {
                    return [];
                }
        }
    }
    public function messages()
    {
        return [
        ];
    }
    protected function prepareForValidation()
    {
        $id = $this->route('id');
        if ($id != null)
            $this->merge(['id' => $id]);
    }
}
