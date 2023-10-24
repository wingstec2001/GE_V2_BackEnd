<?php

namespace App\Http\Requests\Api;

use Illuminate\Support\Facades\Log;

class MaterialRequest extends FormRequest
{

    public function authorize()
    {
        //false代表权限验证不通过，返回403错误
        //true代表权限认证通过
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
                        'id' => 'required|exists:m_material'
                    ];
                }
            case 'POST':
                //Add
                {
                    return [
                        'material_id' => 'required|unique:m_material',
                        'material_img' => 'nullable|string',
                        'material_name' => 'required',
                    ];
                }
            case 'PUT': 
                //Update
                return [
                    'id' => 'required|exists:m_material',
                    'material_img' => 'nullable|string',
                ];
            case 'PATCH':
            case 'DELETE': 
                //Delete
                return [
                    'id' => 'required|exists:m_material'
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
