<?php

namespace App\Providers;

use App\Repositories\Decorators\TransactionalDecorator;
use App\Repositories\TaskRepository;
use App\Repositories\TaskRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(TaskRepositoryInterface::class, function () {
            $repository = new TaskRepository();
            return new TransactionalDecorator($repository);
        });
    }
}
