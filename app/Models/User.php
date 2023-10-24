<?php
/*
 * @Author: 張国慶
 * @Date: 2022-02-16 16:16:22
 * @LastEditors: 張国慶
 * @LastEditTime: 2022-03-07 16:25:23
 * @FilePath: /backend/app/Models/User.php
 * @Description: 
 * 
 * Copyright (c) 2022 by Wingstec, All Rights Reserved. 
 */

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Spatie\Permission\Traits\HasRoles; 
use App\Notifications\ResetPasswordNotification;

class User extends Authenticatable implements JWTSubject,MustVerifyEmail
{
    // use HasApiTokens, HasFactory, Notifiable;
    use HasFactory, Notifiable,HasApiTokens;
    use HasRoles;
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'invalid',
        'customer_id'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier() {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims() {
        return [];
    }    

    public function sendPasswordResetNotification($token)
    {
        $app_url =  config('app.url');
        $url =  $app_url.'/reset-password?token=' . $token;

        $this->notify(new ResetPasswordNotification($url));
    }
}
