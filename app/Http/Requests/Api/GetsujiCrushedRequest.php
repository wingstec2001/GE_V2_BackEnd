<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class GetsujiCrushedRequest extends FormRequest
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
                $id = $this->route('id');
                error_log('--------------->GET Request valid ' . $id . ' Some message here.'); {
                    return [
                        'id' => 'required'
                    ];
                }
            case 'POST':
                //Add
                {
                    return [
                      'material_id' => 'required',
                      'yyyymm' => 'required',
                      'total_weight' => 'required',
                    ];
                }
            case 'PUT': 
                //Update
                return [
                    'id' => 'required|exists:t_getsuji_crushed',
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

