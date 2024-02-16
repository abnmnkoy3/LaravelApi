<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

class CustomerResource extends JsonResource
{
    /**
     * Transform the resource into an array. 
     *
     * @param  \Illuminate\Http\Request  $request 
     * @param  \Illuminate\Http\Request  
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        // log::info($this->type);
        // print($request->data);
        if ($this->type == '76fab9d9-da15-4f78-8229-6711ab5b51d3') {
            
            return $this->id;
                
        } 
        return 'test';
    }
}
