<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Task;
use App\Notifications\TaskCreatedNotification;
use App\Repositories\TaskRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;

class TaskService
{
    protected TaskRepositoryInterface $taskRepository;

    public function __construct(TaskRepositoryInterface $taskRepository)
    {
        $this->taskRepository = $taskRepository;
    }

    public function getFilteredTasks(Project $project, array $filters): LengthAwarePaginator
    {
        return $this->taskRepository->all(['project' => $project, 'filters' => $filters]);
    }

    public function createTask(array $taskData, ?UploadedFile $attachment = null): Task
    {
        $task = $this->taskRepository->create($taskData);


        if ($attachment) {
            $task->addMedia($attachment)->toMediaCollection('attachments');
        }

        $task->user->notify(new TaskCreatedNotification($task));

        return $task;
    }

    public function updateTask(Task $task, array $taskData, ?UploadedFile $attachment = null): Task
    {
        $task = $this->taskRepository->update($task, $taskData);


        if ($attachment) {
            $task->clearMediaCollection('attachments');
            $task->addMedia($attachment)->toMediaCollection('attachments');
        }

        return $task;
    }

    public function showTask(Task $task): Task
    {
        return $this->taskRepository->find($task);
    }

    public function deleteTask(Task $task): bool
    {
        $task->clearMediaCollection('attachments');

        return $this->taskRepository->delete($task);
    }
}
