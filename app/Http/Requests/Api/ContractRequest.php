<?php

namespace App\Http\Requests\Api;

use Illuminate\Support\Facades\Log;

class ContractRequest extends FormRequest
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
                $id = $this->route('id');
                error_log('--------------->GET Request valid ' . $id . ' Some message here.'); {
                    return [];
                }
            case 'POST':
                //Add
                {
                    return [
                        // 'contract_id' => 'required|unique:t_contract',
                        'contract_name' => 'required',
                    //    'customer_id' => 'required',
                        'contract_details' => 'required',
                    ];
                }
            case 'PUT':
                //Update
                return [
                    'id' => 'required|exists:t_contract',
                ];
            case 'PATCH':
            case 'DELETE':
                //Delete
                return [
                    'id' => 'required|exists:t_contract'
                ];
            default: {
                    return [];
                }
        }
    }
    public function messages()
    {
        return [];
    }
    protected function prepareForValidation()
    {
        $id = $this->route('id');
        if ($id != null)
            $this->merge(['id' => $id]);
    }
}
