<?php

namespace App\Http\Controllers;

use App\Http\Requests\Task\CreateTaskRequest;
use App\Http\Requests\Task\CrudTaskRequest;
use App\Http\Requests\Task\FilterTaskRequest;
use App\Http\Requests\Task\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Project;
use App\Models\Task;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TaskController extends Controller
{
    protected TaskService $taskService;

    public function __construct(TaskService $taskService)
    {
        $this->taskService = $taskService;
    }

    public function index(FilterTaskRequest $request, Project $project): AnonymousResourceCollection
    {
        $tasks = $this->taskService->getFilteredTasks($project, $request->validated());
        return TaskResource::collection($tasks);
    }

    public function store(CreateTaskRequest $request, Project $project): TaskResource
    {
        $taskData = $request->validated();
        $taskData['project_id'] = $project->id;

        $attachment = $request->file('attachment');

        $task = $this->taskService->createTask($taskData, $attachment);

        return new TaskResource($task);
    }

    public function show(Task $task): TaskResource
    {
        return new TaskResource($this->taskService->showTask($task));
    }

    public function update(UpdateTaskRequest $request, Task $task): TaskResource
    {
        $taskData = $request->validated();

        $attachment = $request->file('attachment');

        $task = $this->taskService->updateTask($task, $taskData, $attachment);

        return new TaskResource($task);
    }

    public function destroy(Task $task): JsonResponse
    {
        return $this->taskService->deleteTask($task)
            ? response()->json(['message' => 'Задача удалена'])
            : response()->json(['message' => 'Не удалось удалить задачу'], 500);
    }
}
