<?php

namespace App\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final readonly class SlackInteractiveMessageHandler
{
    public function __construct(
        public string $id,
    ) {
    }
}
