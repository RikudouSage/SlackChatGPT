<?php

namespace App\Dto;

use App\Enum\ChatGptMessageRole;

final readonly class ChatGptMessage
{
    public function __construct(
        public ChatGptMessageRole $role,
        public string $content,
    ) {
    }

    public static function fromSlackConversationReply(SlackConversationReply $reply): self
    {
        return new self(
            role: $reply->isCurrentBot ? ChatGptMessageRole::ChatGpt : ChatGptMessageRole::User,
            content: $reply->text,
        );
    }
}
