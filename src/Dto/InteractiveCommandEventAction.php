<?php

namespace App\Dto;

final readonly class InteractiveCommandEventAction
{
    public function __construct(
        public string $name,
        public string $type,
        public string $value,
    ) {
    }
}
