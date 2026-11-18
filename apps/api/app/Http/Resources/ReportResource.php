<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'assignment_id' => $this->assignment_id,
            'class' => $this->whenLoaded('class', fn () => ['id' => $this->class->id, 'name' => $this->class->name]),
            'mode' => $this->whenLoaded('assignment', fn () => $this->assignment->mode),
            'report_date' => $this->report_date->toDateString(),
            'status' => $this->status,
            'revision' => $this->revision,
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($i) => [
                'student_id' => $i->student_id, 'status' => $i->status, 'note' => $i->note,
            ])),
            'reviews' => $this->whenLoaded('reviews', fn () => $this->reviews->map(fn ($r) => [
                'decision' => $r->decision, 'note' => $r->note, 'revision' => $r->revision, 'at' => $r->created_at,
            ])),
        ];
    }
}
