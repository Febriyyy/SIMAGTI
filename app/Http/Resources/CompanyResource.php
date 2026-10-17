<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Company */
class CompanyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'industry' => $this->industry,
            'city' => $this->city,
            'status' => $this->status,
            'status_label' => $this->statusLabel(),
            'active_internships_count' => $this->when($this->offsetExists('active_internships_count'), fn () => (int) $this->active_internships_count),
            'internships_count' => $this->whenCounted('internships'),
        ];
    }
}
