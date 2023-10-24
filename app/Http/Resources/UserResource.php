<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    // private $roles;

    // public function __construct($resource, $roles = null) {
    //     // Ensure we call the parent constructor
    //     parent::__construct($resource);
    //     $this->resource = $resource;
    //     $this->roles = $roles; 
    // }
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        // dd($this->roles);
        return[
            'id'=>$this->id,
            'name' => $this->name,
            'email' => $this->email,
            'permissions'=>$this->getAllPermissions()->pluck('name'),
            'userRoles'=>$this->getRoleNames(),
            $this->mergeWhen($this->allRoles!== null, [
                'roles'=>$this->allRoles
            ]),
            'invalid' => $this->invalid?:0
        ];
    }
}
