<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Internship */
class InternshipResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'status_label' => $this->statusLabel(),
            'track' => $this->track,
            'start_date' => $this->start_date->toDateString(),
            'end_date' => $this->end_date->toDateString(),
            'current_week' => $this->currentWeek(),
            'total_weeks' => $this->totalWeeks(),
            'days_left' => $this->daysLeft(),
            'progress_percent' => $this->percent(),
            'student' => $this->whenLoaded('student', fn () => [
                'id' => $this->student->id,
                'name' => $this->student->name,
                'nim' => $this->student->nim,
            ]),
            'company' => $this->whenLoaded('company', fn () => [
                'id' => $this->company->id,
                'name' => $this->company->name,
            ]),
            'mentor' => $this->whenLoaded('mentor', fn () => $this->mentor?->name),
            'lecturer' => $this->whenLoaded('lecturer', fn () => $this->lecturer?->name),
        ];
    }
}
