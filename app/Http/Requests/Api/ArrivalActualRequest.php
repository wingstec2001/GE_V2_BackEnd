<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ArrivalActualRequest extends FormRequest
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
                $arrival_id = $this->route('arrival_id');
                error_log('--------------->GET Request valid ' . $arrival_id . ' Some message here.'); {
                    return [
                        'arrival_id' => 'required|exists:t_arrival_actual'
                    ];
                }
            case 'POST':
                //Add
                {
                    return [
                        //   'customer_id' => 'required',
                        //   'actual_date' => 'required',
                    ];
                }
            case 'PUT':
                //Update
                return [
                    'arrival_id' => 'required|exists:t_arrival_actual',
                ];
            case 'PATCH':
            case 'DELETE':
                //Delete
                return [
                    'arrival_id' => 'required|exists:t_arrival_actual'
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
        $arrival_id = $this->route('arrival_id');
        if ($arrival_id != null)
            $this->merge(['arrival_id' => $arrival_id]);
    }
}
