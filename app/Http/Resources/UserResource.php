<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'facility_id' => $this->facility_id,
            'facility' => $this->whenLoaded('facility', fn () => [
                'id' => $this->facility->id,
                'kode' => $this->facility->kode,
                'nama' => $this->facility->nama,
            ]),
            'roles' => $this->getRoleNames(),
        ];
    }
}
