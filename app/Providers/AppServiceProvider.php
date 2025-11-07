<?php

namespace App\Providers;

use App\Repositories\TaskRepository;
use App\Repositories\TaskRepositoryInterface;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(TaskRepositoryInterface::class, TaskRepository::class);
    }

    public function boot(): void
    {
        Storage::disk('public')->makeDirectory('media');
    }
}
