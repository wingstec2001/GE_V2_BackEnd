<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class CustomerRequest extends FormRequest
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
                            'id' => 'required|exists:m_customer'
                        ];
                    }
                case 'POST':
                    //Add
                    {
                        return [
                          'customer_id' => 'required|unique:m_customer',
                        //   'email' => 'required|unique:m_customer',
                        //   'mobile' => 'required|unique:m_customer',
                          'customer_name' => 'required',
                          'country_id' => 'required',
                        ];
                    }
                case 'PUT': 
                    //Update
                    return [
                        'id' => 'required|exists:m_customer',
                    ];
                case 'PATCH':
                case 'DELETE': 
                    //Delete
                    return [
                        'id' => 'required|exists:m_customer'
                    ];
                default: {
                        return [];
                    }
            }
        }
        public function messages()
        {
            return [
                // 'material_name.required'=>'用户ID必须填写',
                // 'id.exists'=>'用户不存在',
                // 'name.unique' => '用户名已经存在',
                // 'name.required' => '用户名不能为空',
                // 'name.max' => '用户名最大长度为12个字符',
                // 'password.required' => '密码不能为空',
                // 'password.max' => '密码长度不能超过16个字符',
                // 'password.min' => '密码长度不能小于6个字符'
            ];
        }
        protected function prepareForValidation()
        {
            $id = $this->route('id');
            if ($id != null)
                $this->merge(['id' => $id]);
        }
    }

