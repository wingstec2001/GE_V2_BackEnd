<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class BlenderRequest extends FormRequest
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
                        'id' => 'required|exists:t_blender'
                    ];
                }
            case 'POST':
                //Add
                {
                    return [

                    ];
                }
            case 'PUT': 
                //Update
                return [
                    'id' => 'required|exists:t_blender',
                ];
            case 'PATCH':
            case 'DELETE': 
                //Delete
                return [
                    'id' => 'required|exists:t_blender'
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

