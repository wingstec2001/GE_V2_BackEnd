<?php
/*
 * @Author: 張国慶
 * @Date: 2022-02-17 15:02:11
 * @LastEditors: 張国慶
 * @LastEditTime: 2022-03-09 15:32:59
 * @FilePath: /backend/app/Http/Requests/Api/RegisterRequest.php
 * @Description: 
 * 
 * Copyright (c) 2022 by Wingstec, All Rights Reserved. 
 */

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema()
 */
class RegisterRequest extends FormRequest
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
     * @OA\Property(format="string", default="zhangguoqing@wingstec.co.jp", description="email", property="email"),
     * @OA\Property(format="string", default="Wingstec@0222", description="password", property="password"),
     */
    public function rules()
    {
        return [
            'mobile' => 'required|unique:m_customer',
            'country' => 'required',
            'customer_name' => 'required',
            'customer_id' => 'required|unique:m_customer,customer_id',
            'name' => 'required|string|between:2,100',
            'email' => 'required|string|email|max:100|unique:users,email',
            'password' => 'required|string|confirmed|min:6',
        ];
    }
}
