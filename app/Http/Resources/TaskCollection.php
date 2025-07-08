<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class TaskCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'success' => true,
            'data' => [
                'tasks' => $this->collection->map(function ($task) {
                    return [
                        'id' => $task->id,
                        'uuid' => $task->uuid,
                        'slug' => $task->slug,
                        'title' => $task->title,
                        'description' => $task->description,
                        'status' => $task->status,
                        'priority' => $task->priority,
                        'due_date' => $task->due_date,
                        'user_id' => $task->assigned_to,
                    ];
                }),
                
            ]
        ];
    }
}
