<?php

namespace App\Attribute;

use App\Enum\SlackEventName;
use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final readonly class SlackEventHandler
{
    public function __construct(
        public SlackEventName $eventName,
    ) {
    }
}
