<?php

declare(strict_types=1);

namespace AlexanderPoellmann\LaravelPostPlc\Classes;

abstract class PlcBase
{
    /** @var array<string, mixed> */
    protected array $row = [];

    public function add(string $key, mixed $value): void
    {
        $this->row[$key] = $value;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return $this->row;
    }
}
