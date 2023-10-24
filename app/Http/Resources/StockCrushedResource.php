<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class StockCrushedResource extends JsonResource
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
        return [
            'id' => $this->id,
            "material_id" => $this->material_id,
            "stocked_dt" => $this->stocked_dt,
            "note" => $this->note !== null ? $this->note : '',
            "origin" => $this->origin,
            // "processed" => $this->processed,
            "crushed_weight" => $this->crushed_weight,
            "aad_id" => $this->aad_id,
            "crushed_id" => $this->crushed_id,
        ];
    }
}
