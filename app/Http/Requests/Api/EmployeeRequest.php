<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;


class EmployeeRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {

        switch ($this->method()) {
            case 'GET': 
                //Detail
                $id = $this->route('id');
                error_log('--------------->GET Request valid ' . $id . ' Some message here.'); {
                    return [
                        'id' => 'required|exists:m_employee'
                    ];
                }
            case 'POST':
                //Add   
                {
                    return [
                        'employee_id' => 'required|unique:m_employee',
                        'employee_sei' => 'required',
                        'employee_mei' => 'required',
                        'employee_seikn' => 'required',
                        'employee_meikn' => 'required',

                    ];
                }
            case 'PUT': 
                //Update
                return [
                    'id' => 'required|exists:m_employee',
                ];
            case 'PATCH':
            case 'DELETE': 
                //Delete
                return [
                    'id' => 'required|exists:m_employee'
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
