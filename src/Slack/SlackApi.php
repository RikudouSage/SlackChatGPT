<?php

namespace App\Slack;

use App\Dto\SlackButtons;
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

    public function postMessage(string $text, string $channelId, ?string $parentTs, ?SlackButtons $buttons = null): string;

    public function updateMessage(string $text, string $ts, string $channelId): void;

    public function postEphemeralMessage(string $text, string $channelId, string $userId, ?string $parentTs, ?SlackButtons $buttons = null): void;

    public function postEphemeralReply(string $responseUrl, string $text = '', bool $replaceOriginal = true, bool $deleteOriginal = false): void;
}
