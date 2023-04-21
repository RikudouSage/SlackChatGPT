<?php

namespace App\Slack;

use App\Dto\SlackConversationReply;

interface SlackApi
{
    public function getCurrentBotId(): string;

    public function getCurrentUserId(): string;

    /**
     * @param (callable(SlackConversationReply $message): bool)|null $filter
     *
     * @return iterable<SlackConversationReply>
     */
    public function getConversationReplies(string $channelId, string $parentTs, ?callable $filter = null): iterable;

    public function postMessage(string $text, string $channelId, ?string $parentTs): void;
}
