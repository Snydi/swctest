<?php

namespace App\Repositories;

use App\Models\Task;

interface TaskRepositoryInterface
{
    public function all($request);

    public function find(Task $task);

    public function create($request);

    public function update(Task $task, array $data);

    public function delete(Task $task): bool;
}
