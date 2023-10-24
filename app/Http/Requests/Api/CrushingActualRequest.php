<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class CrushingActualRequest extends FormRequest
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
                {
                    return [
                        'crushed_id' => 'required|exists:t_crushing_actual'
                    ];
                }
            case 'POST':
                //Add
                {
                    return [
                        'material_id' => 'required',

                    ];
                }
            case 'PUT':
                //Update
                return [
                    'crushed_id' => 'required|exists:t_crushing_actual',
                ];
            case 'PATCH':
            case 'DELETE':
                //Delete
                return [
                    'crushed_id' => 'required|exists:t_crushing_actual'
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
        $crushed_id = $this->route('crushed_id');
        if ($crushed_id != null)
            $this->merge(['crushed_id' => $crushed_id]);
    }
}
