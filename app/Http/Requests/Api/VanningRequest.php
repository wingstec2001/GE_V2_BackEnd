<?php

namespace App\Http\Requests\Api;

use Illuminate\Support\Facades\Log;

class VanningRequest extends FormRequest
{

    public function authorize()
    {
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
                $vanning_id = $this->route('vanning_id');
                error_log('--------------->GET Request valid ' . $vanning_id . ' Some message here.'); {
                    return [];
                }
            case 'POST':
                //Add
                {
                    return [
                      'contract_id' => 'required|unique:t_vanning',
                      'vanning_date' => 'required',
                      'vanning_time' => 'required',
                    //   'vanning_details' => 'required',
                      // 'vanning_id' => 'required', 
                      // 'customer_id' => 'required',
                      // 'vanning_order' => 'required',
                      // 'vanning_goods_name' => 'required',
                      // 'vanning_weight' => 'required',
                    ];
                }
            case 'PUT': 
                //Update
                return [
                    'vanning_id' => 'required|exists:t_vanning',
                ];
            case 'PATCH':
            case 'DELETE': 
                //Delete
                return [
                    'vanning_id' => 'required|exists:t_vanning'
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
        $vanning_id = $this->route('vanning_id');
        if ($vanning_id != null)
            $this->merge(['vanning_id' => $vanning_id]);
    }
}
