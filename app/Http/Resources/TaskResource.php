<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'success'=> true,
            'message' => 'Task fully created',
            'data'=>[
               'task'=>[
                'uuid' => $this->uuid,
                    'title' => $this->title,
                    'description' => $this->description,
                    'slug' =>$this->slug,
                    'status' => $this->status,
                    'priority' => $this->priority,
                    'team_id' =>$this->team_id,
                    'due_date' => $this->due_date,
                    'created_at' => $this->created_at,
                    'updated_at' => $this->updated_at
               ]
            ]
            ];
    }
}
