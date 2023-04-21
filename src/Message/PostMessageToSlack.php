<?php

namespace App\Message;

final class PostMessageToSlack
{
    public function __construct(
        public string $message,
        public string $channelId,
        public ?string $parentTs,
    ) {
    }
}
