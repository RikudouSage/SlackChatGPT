<?php

namespace App\Message;

final readonly class RetryReplyToSlackMessage
{
    public function __construct(
        public ReplyToSlackMessage $message,
        public string $responseUrl,
    ) {
    }
}
