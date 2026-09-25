<?php

declare(strict_types=1);

namespace AlexanderPoellmann\LaravelPostPlc\Classes;

use AlexanderPoellmann\LaravelPostPlc\DataTransferObjects\ColloArticleRow;
use AlexanderPoellmann\LaravelPostPlc\DataTransferObjects\ColloCode;
use AlexanderPoellmann\LaravelPostPlc\DataTransferObjects\ColloRow;

class Collo extends PlcBase
{
    public function height(int|string $height): self
    {
        $this->add('Height', (int) $height);

        return $this;
    }

    public function width(int|string $width): self
    {
        $this->add('Width', (int) $width);

        return $this;
    }

    public function length(int|string $length): self
    {
        $this->add('Length', (int) $length);

        return $this;
    }

    public function weight(int|float $weight): self
    {
        $this->add('Weight', (float) $weight);

        return $this;
    }

    /** @param list<ColloCode|array<string, mixed>> $codes */
    public function codes(array $codes): self
    {
        $this->add('ColloCodeList', $codes);

        return $this;
    }

    /** @param list<ColloArticleRow|array<string, mixed>> $articles */
    public function articles(array $articles): self
    {
        $this->add('ColloArticleList', $articles);

        return $this;
    }

    public function get(): ColloRow
    {
        return ColloRow::from($this->row);
    }
}
