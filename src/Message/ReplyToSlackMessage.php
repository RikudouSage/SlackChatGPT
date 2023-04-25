<?php

namespace App\Message;

final readonly class ReplyToSlackMessage
{
    public function __construct(
        public string $message,
        public string $channelId,
        public ?string $parentTs,
        public string $userId,
        public bool $threadExists,
    ) {
    }
}
