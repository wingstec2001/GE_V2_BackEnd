<?php
/*
 * @Author: 張国慶
 * @Date: 2022-03-01 14:02:23
 * @LastEditors: 張国慶
 * @LastEditTime: 2022-03-08 15:11:19
 * @FilePath: /backend/app/Http/Controllers/EmailVerificationController.php
 * @Description: 
 * 
 * Copyright (c) 2022 by Wingstec, All Rights Reserved. 
 */

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Support\Facades\Log;

class EmailVerificationController extends Controller
{
    public function sendVerificationEmail(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return $this->failed(['Already Verified']);
        }

        $request->user()->sendEmailVerificationNotification();
        return $this->success(['verification-link-sent']);
    }

    public function verify(Request $request)
    {
        //check id & hash
        if (
            !hash_equals(
                (string) $request->route('id'),
                (string) $request->user()->getKey()
            )
            ||
            !hash_equals(
                (string) $request->route('hash'),
                sha1($request->user()->getEmailForVerification())
            )
        ) {
            $message = "リンクが無効です!";
            Log::warning($message);
            return $this->notFound([$message]);
        }
        if ($request->user()->hasVerifiedEmail()) {
            
            return $this->setStatusCode(204)->success('no content');
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
            $request->user()->assignRole(['Customer']);
        }

        return $this->setStatusCode(204)->success('no content');
    }
}
