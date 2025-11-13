<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'priority' => $this->priority,
            'due_date' => $this->due_date,
            // On inclut les objets liés s'ils sont chargés, sinon null
            'assigned_to_user' => $this->whenLoaded('assignedTo', function() {
                return ['id' => $this->assignedTo->id, 'name' => $this->assignedTo->name];
            }),
            'created_by_user' => $this->whenLoaded('createdBy', function() {
                return ['id' => $this->createdBy->id, 'name' => $this->createdBy->name];
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}