<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema()
 */
class LoginRequest extends FormRequest
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
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string','min:6'],
        ];
    }
}
