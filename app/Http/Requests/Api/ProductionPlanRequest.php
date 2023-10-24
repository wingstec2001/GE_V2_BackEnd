<?php

namespace App\Http\Requests\Api;

use Illuminate\Support\Facades\Log;

class ProductionPlanRequest extends FormRequest
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
                        'id' => 'required|exists:t_production_plan'
                    ];
                }
            case 'POST':
                //Add
                {
                    return [
                      'plan_date' => 'required', 
                      'product_id' => 'required',
                      'route_id' => 'required',
                    ];
                }
            case 'PUT': 
                //Update
                return [
                    'id' => 'required|exists:t_production_plan',
                ];
            case 'PATCH':
            case 'DELETE': 
                //Delete
                return [
                    'id' => 'required|exists:t_production_plan'
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
