<?php

namespace Tests\Unit\Services;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskCreatedNotification;
use App\Repositories\TaskRepositoryInterface;
use App\Services\TaskService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;
use Tests\TestCase;

class TaskServiceTest extends TestCase
{
    use DatabaseTransactions;

    private TaskService $taskService;
    private TaskRepositoryInterface $taskRepository;
    private Project $project;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->taskRepository = $this->createMock(TaskRepositoryInterface::class);
        $this->taskService = new TaskService($this->taskRepository);

        $this->user = User::factory()->create();
        $this->project = Project::factory()->create();
    }

    public function test_get_filtered_tasks()
    {
        $filters = ['status' => 'in_progress'];
        $expectedPaginator = new LengthAwarePaginator([], 0, 15);

        $this->taskRepository
            ->expects($this->once())
            ->method('all')
            ->with(['project' => $this->project, 'filters' => $filters])
            ->willReturn($expectedPaginator);

        $result = $this->taskService->getFilteredTasks($this->project, $filters);

        $this->assertSame($expectedPaginator, $result);
    }

    public function test_create_task_without_attachment()
    {
        Notification::fake();

        $taskData = [
            'header' => 'Тестовая задача',
            'description' => 'Тестовое описание',
            'status' => 'planned',
            'user_id' => $this->user->id,
            'project_id' => $this->project->id,
        ];

        $task = Task::factory()->make($taskData);

        $this->taskRepository
            ->expects($this->once())
            ->method('create')
            ->with($taskData)
            ->willReturn($task);

        $result = $this->taskService->createTask($taskData);

        $this->assertSame($task, $result);
        Notification::assertSentTo($task->user, TaskCreatedNotification::class);
    }

    public function test_create_task_with_attachment()
    {
        Notification::fake();
        Storage::fake('public');

        $taskData = [
            'header' => 'Тестовая задача с вложением',
            'description' => 'Тестовое описание с вложением',
            'status' => 'planned',
            'user_id' => $this->user->id,
            'project_id' => $this->project->id,
        ];

        $task = Task::factory()->create($taskData);

        $fileContent = 'содержание PDF файла';
        $attachment = $this->createFileWithContent('документ.pdf', $fileContent, 'application/pdf');

        $this->taskRepository
            ->expects($this->once())
            ->method('create')
            ->with($taskData)
            ->willReturn($task);

        $result = $this->taskService->createTask($taskData, $attachment);

        $this->assertSame($task, $result);
        $this->assertTrue($task->hasMedia('attachments'));
        $this->assertCount(1, $task->getMedia('attachments'));

        $media = $task->getFirstMedia('attachments');
        $this->assertEquals('документ.pdf', $media->file_name);

        Notification::assertSentTo($task->user, TaskCreatedNotification::class);
    }

    public function test_update_task_without_attachment()
    {
        $task = Task::factory()->create(['user_id' => $this->user->id]);
        $taskData = ['header' => 'Обновленный заголовок', 'status' => 'done'];

        $this->taskRepository
            ->expects($this->once())
            ->method('update')
            ->with($task, $taskData)
            ->willReturn($task);

        $result = $this->taskService->updateTask($task, $taskData);

        $this->assertSame($task, $result);
    }

    /**
     * @throws FileDoesNotExist
     * @throws FileIsTooBig
     */
    public function test_update_task_with_attachment()
    {
        Storage::fake('public');

        $task = Task::factory()->create(['user_id' => $this->user->id]);
        $taskData = ['header' => 'Обновленный заголовок с вложением'];

        $fileContent = 'Новое содержание PDF файла';
        $attachment = $this->createFileWithContent('новый_документ.pdf', $fileContent, 'application/pdf');

        $existingContent = 'Старое содержание PDF файла';
        $existingAttachment = $this->createFileWithContent('старый_документ.pdf', $existingContent, 'application/pdf');
        $task->addMedia($existingAttachment)->toMediaCollection('attachments');

        $this->taskRepository
            ->expects($this->once())
            ->method('update')
            ->with($task, $taskData)
            ->willReturn($task);

        $result = $this->taskService->updateTask($task, $taskData, $attachment);

        $this->assertSame($task, $result);
        $this->assertCount(1, $task->getMedia('attachments'));

        $media = $task->getFirstMedia('attachments');
        $this->assertEquals('новый_документ.pdf', $media->file_name);
    }

    /**
     * @throws FileDoesNotExist
     * @throws FileIsTooBig
     */
    public function test_update_task_replaces_existing_attachment()
    {
        Storage::fake('public');

        $task = Task::factory()->create(['user_id' => $this->user->id]);
        $taskData = ['header' => 'Задача с заменой вложения'];


        $oldContent1 = 'Старое содержание PDF файла';
        $oldContent2 = 'Старое содержание PDF файла 2';
        $oldAttachment1 = $this->createFileWithContent('старый1.pdf', $oldContent1, 'application/pdf');
        $oldAttachment2 = $this->createFileWithContent('старый2.pdf', $oldContent2, 'application/pdf');
        $task->addMedia($oldAttachment1)->toMediaCollection('attachments');
        $task->addMedia($oldAttachment2)->toMediaCollection('attachments');


        $newContent = 'Замена содержания';
        $newAttachment = $this->createFileWithContent('новый.pdf', $newContent, 'application/pdf');

        $this->taskRepository
            ->expects($this->once())
            ->method('update')
            ->with($task, $taskData)
            ->willReturn($task);

        $result = $this->taskService->updateTask($task, $taskData, $newAttachment);

        $this->assertSame($task, $result);
        $this->assertCount(1, $task->getMedia('attachments'));

        $media = $task->getFirstMedia('attachments');
        $this->assertEquals('новый.pdf', $media->file_name);
    }

    public function test_show_task()
    {
        $task = Task::factory()->create();

        $this->taskRepository
            ->expects($this->once())
            ->method('find')
            ->with($task)
            ->willReturn($task);

        $result = $this->taskService->showTask($task);

        $this->assertSame($task, $result);
    }

    public function test_delete_task_without_media()
    {
        $task = Task::factory()->create();

        $this->taskRepository
            ->expects($this->once())
            ->method('delete')
            ->with($task)
            ->willReturn(true);

        $result = $this->taskService->deleteTask($task);

        $this->assertTrue($result);
    }

    /**
     * @throws FileDoesNotExist
     * @throws FileIsTooBig
     */
    public function test_delete_task_with_media()
    {
        Storage::fake('public');

        $task = Task::factory()->create();

        $content1 = 'Старое содержание PDF файла';
        $content2 = 'Старое содержание PDF файла 2';
        $attachment1 = $this->createFileWithContent('документ1.pdf', $content1, 'application/pdf');
        $attachment2 = $this->createFileWithContent('документ2.pdf', $content2, 'application/pdf');
        $task->addMedia($attachment1)->toMediaCollection('attachments');
        $task->addMedia($attachment2)->toMediaCollection('attachments');

        $this->assertCount(2, $task->getMedia('attachments'));

        $this->taskRepository
            ->expects($this->once())
            ->method('delete')
            ->with($task)
            ->willReturn(true);

        $result = $this->taskService->deleteTask($task);

        $this->assertTrue($result);
    }

    public function test_delete_task_returns_false_when_repository_fails()
    {
        $task = Task::factory()->create();

        $this->taskRepository
            ->expects($this->once())
            ->method('delete')
            ->with($task)
            ->willReturn(false);

        $result = $this->taskService->deleteTask($task);

        $this->assertFalse($result);
    }

    public function test_create_task_with_text_attachment()
    {
        Notification::fake();
        Storage::fake('public');

        $taskData = [
            'header' => 'Тестовая задача с текстовым файлом',
            'description' => 'Тестовое описание с текстовым файлом',
            'status' => 'in_progress',
            'user_id' => $this->user->id,
            'project_id' => $this->project->id,
        ];

        $task = Task::factory()->create($taskData);


        $textContent = "Это текстовый файл для тестирования\nСодержащий несколько строк текста\nДля проверки работы медиа библиотеки";
        $textAttachment = $this->createFileWithContent('текстовый_файл.txt', $textContent, 'text/plain');

        $this->taskRepository
            ->expects($this->once())
            ->method('create')
            ->with($taskData)
            ->willReturn($task);

        $result = $this->taskService->createTask($taskData, $textAttachment);

        $this->assertSame($task, $result);
        $this->assertTrue($task->hasMedia('attachments'));
        $this->assertCount(1, $task->getMedia('attachments'));

        $media = $task->getFirstMedia('attachments');
        $this->assertEquals('текстовый_файл.txt', $media->file_name);

        Notification::assertSentTo($task->user, TaskCreatedNotification::class);
    }


    private function createFileWithContent(string $filename, string $content, string $mimeType): UploadedFile
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'testfile');
        file_put_contents($tempFile, $content);

        return new UploadedFile(
            $tempFile,
            $filename,
            $mimeType,
            null,
            true
        );
    }

    protected function tearDown(): void
    {
        $tempFiles = glob(sys_get_temp_dir() . '/testfile*');
        foreach ($tempFiles as $tempFile) {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }

        parent::tearDown();
    }
}
