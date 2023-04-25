<?php

namespace App\Message;

use App\Dto\SlackButtons;

final class PostMessageToSlack
{
    public function __construct(
        public string $message,
        public string $channelId,
        public ?string $parentTs,
        public bool $ephemeralMessage = false,
        public ?string $userId = null,
        public ?SlackButtons $buttons = null,
    ) {
    }
}
