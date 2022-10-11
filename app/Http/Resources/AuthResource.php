<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AuthResource extends JsonResource
{
    private $authorization;

    public function __construct(AppUserResource $resource, array $authorization)
    {
        parent::__construct($resource);
        $this->authorization = $authorization;
    }

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'user' => $this->resource,
            'authorization' => $this->authorization,
        ];
    }
}
