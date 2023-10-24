<?php
/*
 * @Author: 張国慶
 * @Date: 2022-02-16 16:16:22
 * @LastEditors: 張国慶
 * @LastEditTime: 2022-03-01 16:05:43
 * @FilePath: /backend/app/Traits/RecordSignature.php
 * @Description: 
 * 
 * Copyright (c) 2022 by Wingstec, All Rights Reserved. 
 */

namespace App\Traits;

use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

trait RecordSignature
{
    protected static function boot()
    {
        parent::boot();

        static::updating(function ($model) {

            $model->updated_by = Auth::User()->name;
        });

        static::creating(function ($model) {

            $user = Auth::User();
            if($user){
                $model->created_by = $user->name;
                $model->updated_by = $user->name;
                $model->updated_at = Carbon::now();
            }else{
                $model->created_by = 'register';
                $model->updated_by = 'register';
                $model->updated_at = Carbon::now();
            }

        });
        static::deleted(function ($model) {

            if(property_exists( $model,'deleted_by')){
                $model->deleted_by = Auth::User()->name;
                $model->save();
            }
            
        });
    }
}
