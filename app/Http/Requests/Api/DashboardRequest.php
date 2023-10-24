<?php

namespace App\Http\Requests\Api;

use Illuminate\Support\Facades\Log;

class DashboardRequest extends FormRequest
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
                    return [
                        'id' => 'required|exists:t_dashboard'
                    ];
                }
            case 'POST':
                //Add
                {
                    return [
                      'dashboard_title' => 'required',
                      'text' => 'required',
                      'fontsize' => 'required',
                      'fontcolor' => 'required',
                    ];
                }
            case 'PUT': 
                //Update
                return [
                    'id' => 'required|exists:t_dashboard',
                ];
            case 'PATCH':
            case 'DELETE': 
                //Delete
                return [
                    'id' => 'required|exists:t_dashboard'
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
