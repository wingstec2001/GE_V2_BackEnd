<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ArrivalPelletRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
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
                $aad_id = $this->route('aad_id');
                error_log('--------------->GET Request valid ' . $aad_id . ' Some message here.'); {
                    return [
                        'aad_id' => 'required|exists:t_arrival_pellets'
                    ];
                }
            case 'POST':
                //Add
                {
                    return [
                        'product_id' => 'required',
                        'arrival_weight' => 'required',
                    ];
                }
            case 'PUT':
                //Update
                return [
                    'aad_id' => 'required|exists:t_arrival_pellets',
                ];
            case 'PATCH':
            case 'DELETE':
                //Delete
                return [
                    'aad_id' => 'required|exists:t_arrival_pellets'
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
        $aad_id = $this->route('aad_id');
        if ($aad_id != null)
            $this->merge(['aad_id' => $aad_id]);
    }
}
