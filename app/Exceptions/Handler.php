<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use App\Api\Helpers\ExceptionReport;
use Illuminate\Support\Facades\Log;
class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function render($request, Throwable $exception)
    {
         //ajax请求我们才捕捉异常
        //  if ($request->ajax()){
            // 将方法拦截到自己的ExceptionReport
            
            // user permission
            // if ($exception instanceof \Spatie\Permission\Exceptions\UnauthorizedException) {
            //     LOG::Info($request.PHP_EOL.$exception);
            //     return response()->json(['User have not permission for this page access.']);
            // }
            // if($exception instanceof \Illuminate\Database\QueryException){
            //     Log::emergency($request.PHP_EOL.$exception);
            //     return response()->json(['databae error.']);
            // }
            LOG::error($request.PHP_EOL.$exception);
            $reporter = ExceptionReport::make($exception);
            if ($reporter->shouldReturn()){
                return $reporter->report();
            }
            
            // if(config('app.debug')){
            //     //开发环境，则显示详细错误信息
                 return parent::render($request, $exception);
            // }else{
                //线上环境,未知错误，则显示500
                return $reporter->prodReport();
            // }
        // }
        // return parent::render($request, $exception);
    }
}
