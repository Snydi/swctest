<?php

namespace App\Repositories;

use App\Models\Task;
use Illuminate\Pagination\LengthAwarePaginator;

class TaskRepository implements TaskRepositoryInterface
{
    public function all($request): LengthAwarePaginator
    {
        $project = $request['project'];
        $filters = $request['filters'];

        $query = Task::where('project_id', $project->id);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['completed_at'])) {
            $query->whereDate('completed_at', $filters['completed_at']);
        }

        if (isset($filters['completed_from']) || isset($filters['completed_to'])) {
            if (isset($filters['completed_from'])) {
                $query->whereDate('completed_at', '>=', $filters['completed_from']);
            }
            if (isset($filters['completed_to'])) {
                $query->whereDate('completed_at', '<=', $filters['completed_to']);
            }
        }

        return $query->with(['user', 'project'])
            ->orderBy('completed_at', 'desc')
            ->paginate(15);
    }

    public function find(Task $task): Task
    {
        return $task;
    }

    public function create($request): Task
    {
        return Task::create($request);
    }

    public function update(Task $task, array $data): ?Task
    {
        $task->update($data);
        return $task->fresh();
    }

    public function delete(Task $task): bool
    {
        return $task->delete();
    }
}
