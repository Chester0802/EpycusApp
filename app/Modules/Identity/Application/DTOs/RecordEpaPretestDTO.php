<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\DTOs;

final readonly class RecordEpaPretestDTO
{
    public function __construct(
        public int $userId,
        public int $item2,
        public int $item5,
        public int $item7,
        public int $item10,
        public int $item11,
        public int $item12,
        public int $item13,
        public int $item14,
    ) {}

    public function totalScore(): int
    {
        return $this->item2
            + $this->item5
            + $this->item7
            + $this->item10
            + $this->item11
            + $this->item12
            + $this->item13
            + $this->item14;
    }
}
