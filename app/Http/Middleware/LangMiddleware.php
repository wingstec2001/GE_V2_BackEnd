<?php
/*
 * @Author: 張国慶
 * @Date: 2022-03-28 15:33:10
 * @LastEditors: 張国慶
 * @LastEditTime: 2022-03-28 15:41:58
 * @FilePath: /backend/app/Http/Middleware/LangMiddleware.php
 * @Description: 
 * 
 * Copyright (c) 2022 by Wingstec, All Rights Reserved. 
 */

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class LangMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // en ja zh_CN
        $local = ($request->hasHeader('lang')) ? $request->header('lang') : 'en';


  
        app()->setLocale($local);
        return $next($request);
    }
}
