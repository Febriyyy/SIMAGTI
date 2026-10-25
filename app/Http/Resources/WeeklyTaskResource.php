<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\WeeklyTask */
class WeeklyTaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'week_number' => $this->week_number,
            'day' => $this->day,
            'day_label' => $this->dayLabel(),
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'status_label' => $this->statusLabel(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'evidences_count' => $this->whenCounted('evidences'),
            'comments_count' => $this->whenCounted('comments'),
        ];
    }
}
