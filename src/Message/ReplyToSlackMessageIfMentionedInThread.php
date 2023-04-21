<?php

namespace App\Message;

final readonly class ReplyToSlackMessageIfMentionedInThread
{
    public function __construct(
        public ReplyToSlackMessage $message,
    ) {
    }
}
