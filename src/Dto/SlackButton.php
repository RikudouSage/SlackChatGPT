<?php

namespace App\Dto;

final class SlackButton
{
    public function __construct(
        public string $id,
        public string $text,
        public string $value,
    ) {
    }
}
