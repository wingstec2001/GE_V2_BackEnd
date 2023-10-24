<?php
/*
 * @Author: 張国慶
 * @Date: 2022-03-04 15:38:19
 * @LastEditors: 張国慶
 * @LastEditTime: 2022-03-07 15:19:40
 * @FilePath: /backend/app/Http/Controllers/NewPasswordController.php
 * @Description: 
 * 
 * Copyright (c) 2022 by Wingstec, All Rights Reserved. 
 */

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Validation\Rules\Password as RulesPassword;
use Illuminate\Validation\ValidationException;
class NewPasswordController extends Controller
{
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status == Password::RESET_LINK_SENT) {
            // return [
            //     'status' => __($status)
            // ];
            return $this->setStatusCode(201)->success([__($status)]);
        }

        // throw ValidationException::withMessages([
        //     'email' => [trans($status)],
        // ]);

        return $this->failed([ __($status)], 400);
    }

    public function reset(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|string|confirmed|min:6',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($request->password),
                    'remember_token' => Str::random(60),
                ])->save();

                $user->tokens()->delete();

                event(new PasswordReset($user));
            }
        );

        if ($status == Password::PASSWORD_RESET) {
            return response([
                'message'=> 'Password reset successfully'
            ]);
        }

        // return response([
        //     'message'=> __($status)
        // ], 500);
        return $this->failed([ __($status)], 400);
    }


}
