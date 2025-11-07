<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TaskControllerTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;
    private Project $project;
    private Task $task;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->project = Project::factory()->create();
        $this->task = Task::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
        ]);

        $this->token = $this->user->createToken('test-token')->plainTextToken;
    }

    public function test_index_returns_tasks_for_project()
    {
        Task::factory()->count(3)->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->withToken($this->token)
            ->getJson("/api/projects/{$this->project->id}/tasks");

        $response->assertOk()
            ->assertJsonCount(4, 'data');
    }

    public function test_store_creates_new_task()
    {
        Notification::fake();
        Storage::fake('local');

        $taskData = [
            'header' => 'Новая задача',
            'description' => 'Описание новой задачи',
            'status' => 'planned',
            'user_id' => $this->user->id,
        ];

        $response = $this->withToken($this->token)
            ->postJson("/api/projects/{$this->project->id}/tasks", $taskData);

        $response->assertCreated()
            ->assertJson([
                'data' => [
                    'header' => 'Новая задача',
                    'description' => 'Описание новой задачи',
                    'status' => 'planned',
                ]
            ]);

        $this->assertDatabaseHas('tasks', [
            'header' => 'Новая задача',
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
        ]);
    }

    public function test_show_returns_task()
    {
        $response = $this->withToken($this->token)
            ->getJson("/api/tasks/{$this->task->id}");

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $this->task->id,
                    'header' => $this->task->header,
                ]
            ]);
    }

    public function test_update_task()
    {
        Storage::fake('local');

        $updateData = [
            'header' => 'Обновленный заголовок',
            'description' => 'Обновленное описание',
            'status' => 'done',
        ];

        $response = $this->withToken($this->token)
            ->postJson("/api/tasks/{$this->task->id}/update", $updateData);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'header' => 'Обновленный заголовок',
                    'description' => 'Обновленное описание',
                    'status' => 'done',
                ]
            ]);

        $this->assertDatabaseHas('tasks', [
            'id' => $this->task->id,
            'header' => 'Обновленный заголовок',
            'status' => 'done',
        ]);
    }

    public function test_delete_task()
    {
        $response = $this->withToken($this->token)
            ->deleteJson("/api/tasks/{$this->task->id}");

        $response->assertOk()
            ->assertJson(['message' => 'Задача удалена']);

        $this->assertDatabaseMissing('tasks', ['id' => $this->task->id]);
    }

    public function test_unauthenticated_access()
    {
        $this->getJson("/api/projects/{$this->project->id}/tasks")->assertUnauthorized();
        $this->postJson("/api/projects/{$this->project->id}/tasks")->assertUnauthorized();
        $this->getJson("/api/tasks/{$this->task->id}")->assertUnauthorized();
        $this->postJson("/api/tasks/{$this->task->id}/update")->assertUnauthorized();
        $this->deleteJson("/api/tasks/{$this->task->id}")->assertUnauthorized();
    }
}
