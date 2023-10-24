<?php
/*
 * @Author: 張国慶
 * @Date: 2022-02-16 16:16:22
 * @LastEditors: 張国慶
 * @LastEditTime: 2022-03-09 16:55:52
 * @FilePath: /backend/app/Http/Controllers/AuthController.php
 * @Description: 
 * 
 * Copyright (c) 2022 by Wingstec, All Rights Reserved. 
 */

namespace App\Http\Controllers;

use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\RegisterRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Customer;
use App\Http\Resources\AuthResource;
use App\Models\Area;
use App\Models\Country;
use Illuminate\Support\Facades\Log;
use Illuminate\Auth\Events\Registered;

class AuthController extends Controller
{

    /**
     * @OA\Post(
     *   tags={"Auth"},
     *   path="/auth/login",
     *   summary="login",
     *   @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="application/x-www-form-urlencoded",
     *             @OA\Schema(
     *                 type="object",
     *                 ref="#/components/schemas/LoginRequest",
     *             )
     *         )
     *     ),
     *   @OA\Response(response=200, description="OK"),
     *   @OA\Response(response=401, description="Unauthorized"),
     *   @OA\Response(response=404, description="Not Found")
     * )
     */
    public function login(LoginRequest $request)
    {
        $token = Auth::guard('api')->attempt(['email' => $request->email, 'password' => $request->password]);
        $user = Auth::guard('api')->user();
        if ($token) {
            if ($user->invalid) {
                LOG::Info($user->name . ' try to login');
                return $this->failed(['account is invalid'], 400);
            }
            LOG::Info($user->name . ' login success');
            return $this->setStatusCode(201)->success(['accessToken' => 'bearer ' . $token]);
        }
        LOG::Info($request . ' login failed ');
        return $this->failed(['メール・パスワードが一致しません。'], 400);
    }
    /**
     * @description: 
     * @param {RegisterRequest} $request
     * @return {*}
     */
    public function register(RegisterRequest $request)
    {

        return DB::transaction(function () use ($request) {

            $customer =  $request->all();
            unset($customer['password']);
            unset($customer['name']);
            unset($customer['email']);
            
           
            $country = Country::firstOrCreate(
                ['country_name_eng' =>   $request->input('country.countryName')],
                ['country_id' =>  $request->input('country.countryShortCode')]
            );
            if($customer['region']){
                // $area = Area::firstOrCreate(
                //     ['area_name' =>  request('region.name')],
                //     ['area_id' => request('region.shortCode')]
                // );
                // TODO: region code not unique,
                // TODO: customer email field
                $customer['area_id'] =   $request->input('region.name');
            }
            $customer['country_id'] =  $country->country_id;
            $id = Customer::create($customer)->id;
            $user = User::create(
                [
                    'password' => bcrypt($request->password),
                    'name' => $request->name,
                    'email' => $request->email,
                    "customer_id" => $id,
                ]
            );
            $token = Auth::guard('api')->attempt(['email' => $request->email, 'password' => $request->password]);
            $user->assignRole(['Guest']);
            event(new Registered($user));
            // return $this->success(['user' => $user]);
            LOG::Info($user->name . 'register success');

            return $this->setStatusCode(201)->success([
                'accessToken' => 'bearer ' . $token,
                'user' => $user
            ]);
        });
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        Auth::guard('api')->logout();
        return $this->success('User successfully signed out');
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function userInfo()
    {
        $user = Auth::guard('api')->user();
        return $this->success(new AuthResource($user));
    }
}
