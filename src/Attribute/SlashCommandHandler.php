<?php

namespace App\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final readonly class SlashCommandHandler
{
    public function __construct(
        public string $name,
    ) {
    }
}
