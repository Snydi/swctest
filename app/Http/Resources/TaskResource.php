<?php

namespace App\Http\Resources;

use App\Models\Task;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Task
 */
class TaskResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'header' => $this->header,
            'description' => $this->description,
            'status' => $this->status,
            'completed_at' => $this->completed_at?->toDateString(),
            'user' => new UserResource($this->whenLoaded('user')),
            'project' => new ProjectResource($this->whenLoaded('project')),
            'attachment_url' => $this->getFirstMediaUrl('attachments'),
        ];
    }
}
