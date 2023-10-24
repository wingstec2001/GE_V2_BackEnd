<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use App\Api\Helpers\ApiResponse;
class Controller extends BaseController
{
    /**
     * @OA\Info(
     *      version="1.0.0",
     *      title="GreenEarth Api Demo Documentation",
     *      description="L5 Swagger OpenApi description",
     *      @OA\Contact(
     *          email="zhangguoqing@wingstec.co.jp"
     *      ),
     *      @OA\License(
     *          name="Apache 2.0",
     *          url="http://www.apache.org/licenses/LICENSE-2.0.html"
     *      )
     * )
     *
     * @OA\SecurityScheme(
     *
     *   securityScheme="bearerAuth",
     *
     *   type="http",
     *
     *   scheme="bearer"
     *
     * )
     *
     * @OA\Server(
     *      url=L5_SWAGGER_CONST_HOST,
     *      description="Demo API Server"
     * )

     *
     * @OA\Tag(
     *     name="Projects",
     *     description="API Endpoints of Projects"
     * )
     */
    // use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
    use ApiResponse;
}
