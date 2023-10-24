<?php

namespace App\Api\Helpers;

use Throwable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Illuminate\Support\Facades\Log;

class ExceptionReport
{
    use ApiResponse;

    /**
     * @var Throwable
     */
    public $exception;
    /**
     * @var Request
     */
    public $request;

    /**
     * @var
     */
    protected $report;

    /**
     * ExceptionReport constructor.
     * @param Request $request
     * @param Exception $exception
     */
    function __construct(Request $request, Throwable $exception)
    {
        $this->request = $request;
        $this->exception = $exception;
    }

    /**
     * @var array
     */
    //当抛出这些异常时，可以使用我们定义的错误信息与HTTP状态码
    //可以把常见异常放在这里
    public $doReport = [
        UnauthorizedHttpException::class => ['UnauthorizedHttpException', 401],
        AuthenticationException::class => ['AuthenticationException', 401],
        ModelNotFoundException::class => ['ModelNotFoundException', 404],
        AuthorizationException::class => ['AuthorizationException', 403],
        ValidationException::class => [],
        \Spatie\Permission\Exceptions\UnauthorizedException::class => [],
        TokenInvalidException::class => ['TokenInvalidException', 401],
        // TokenInvalidException::class => ['TokenInvalidException', 400],
        NotFoundHttpException::class => ['NotFoundHttpException', 404],
        MethodNotAllowedHttpException::class => ['MethodNotAllowedHttpException', 405],
        // QueryException::class => ['QueryException', 401],
        // 500 DB Error
        QueryException::class => []
    ];

    public function register($className, callable $callback)
    {

        $this->doReport[$className] = $callback;
    }

    /**
     * @return bool
     */
    public function shouldReturn()
    {
        //只有请求包含是json或者ajax请求时才有效
        //        if (! ($this->request->wantsJson() || $this->request->ajax())){
        //
        //            return false;
        //        }
        foreach (array_keys($this->doReport) as $report) {
            if ($this->exception instanceof $report) {
                $this->report = $report;
                return true;
            }
        }

        return false;
    }

    /**
     * @param Throwable $e
     * @return static
     */
    public static function make(Throwable $e)
    {

        return new static(\request(), $e);
    }

    /**
     * @return mixed
     */
    public function report()
    {
        if ($this->exception instanceof \Spatie\Permission\Exceptions\UnauthorizedException) {
            LOG::Info($this->request . PHP_EOL . $this->exception);
            return  $this->failed(['User have not permission for this page access.'], 422);
        }
        if ($this->exception instanceof QueryException) {
            Log::error($this->request . PHP_EOL . $this->exception);
            $res_id = $this->request->header('request-id');
            return $this->failed([
                'エラーが発生しました。('.$res_id.')',
            ], '500');
            // return response()->json(['databae error.']);
        }
        if ($this->exception instanceof ValidationException) {
            $error = $this->exception->errors();
            $errorList = [];
            foreach ($error as $key => $value) {
                $errorList = array_merge($errorList, $value);
            }
            return $this->failed($errorList, $this->exception->status);
        }
        $message = $this->doReport[$this->report];
        return $this->failed($message[0], $message[1]);
    }
    public function prodReport()
    {
        Log::error($this->request . PHP_EOL . $this->exception);
        $res_id = $this->request->header('request-id');
        return $this->failed([
            'エラーが発生しました。('.$res_id.')',
        ], '500');
    }
}
