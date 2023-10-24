<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
{
    private $permission;

    public function __construct($resource, $permissions = null) {
        // Ensure we call the parent constructor
        parent::__construct($resource);
        $this->resource = $resource;
        $this->permissions = $permissions; 
    }
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return[
            'id'=>$this->id,
            'name' => $this->name,
            'rolePermissions'=>$this->getAllPermissions()->pluck('name'),
            // 'permissions'=>$this->permission->pluck('name')
            $this->mergeWhen($this->permissions!== null, [
                'permissions'=>$this->permissions
            ]),
            // 'permissions'=>$this->when($this->permission!=null, 'permission')->pluck('name'),
        ];
    }
}
