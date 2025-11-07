<?php

namespace App\Repositories\Decorators;

use Illuminate\Support\Facades\DB;

class TransactionalDecorator
{
    public function __construct(
        private object $repository
    )
    {
    }

    public function __call(string $method, array $arguments)
    {
        if (!method_exists($this->repository, $method)) {
            throw new \BadMethodCallException("Метод {$method} не существует.");
        }

        return DB::transaction(fn() => $this->repository->{$method}(...$arguments));
    }
}
