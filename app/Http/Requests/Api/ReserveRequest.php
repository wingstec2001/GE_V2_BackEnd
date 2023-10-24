<?php

namespace App\Http\Requests\Api;

use Illuminate\Support\Facades\Log;

class ReserveRequest extends FormRequest
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
                        'id' => 'required|exists:m_reserve'
                    ];
                }
            case 'POST':
                //Add
                {
                    return [
                        'reserve_id' => 'required|unique:m_reserve',
                        'reserve_name' => 'required',
                        'product_id' => 'required',
                        'reserve_open_dt' => 'required',
                        'reserve_comp_dt' => 'required',
                        'reserve_weight' => 'required',
                        'reserve_price' => 'required',
                        'reserve_maximum' => 'required',
                    ];
                }
            case 'PUT': 
                //Update
                return [
                    'id' => 'required|exists:m_reserve',
                    // 'product_img' => 'mimes:jpeg,bmp,png,gif',
                ];
            case 'PATCH':
            case 'DELETE': 
                //Delete
                return [
                    'id' => 'required|exists:m_reserve'
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
