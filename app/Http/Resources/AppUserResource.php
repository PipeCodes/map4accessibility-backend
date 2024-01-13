<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AppUserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'name' => $this->name,
            'surname' => $this->surname,
            'birthdate' => $this->birthdate,
            'disabilities' => $this->disabilities,
            'auth_providers' => $this->auth_providers,
            'avatar' => $this->avatar,
            'terms_accepted' => $this->terms_accepted,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'account_status' => $this->accountStatus,
        ];
    }
}
