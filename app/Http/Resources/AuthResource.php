<?php
/*
 * @Author: 張国慶
 * @Date: 2022-02-16 16:16:22
 * @LastEditors: 張国慶
 * @LastEditTime: 2022-03-08 15:54:04
 * @FilePath: /backend/app/Http/Resources/AuthResource.php
 * @Description: 
 * 
 * Copyright (c) 2022 by Wingstec, All Rights Reserved. 
 */

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
class AuthResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        // return parent::toArray($request);
        return[
            'id'=>$this->id,
            'name' => $this->name,
            'email' => $this->email,
            'permissions'=>$this->getAllPermissions()->pluck('name'),
            'roles'=>$this->getRoleNames(),
            'verified'=>$this->hasVerifiedEmail()
        ];
    }
}
