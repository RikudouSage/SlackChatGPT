<?php

namespace App\Dto;

final readonly class SlackConversationReply
{
    public function __construct(
        public string $type,
        public string $userId,
        public string $text,
        public string $ts,
        public ?string $threadTs,
        public ?int $replyCount,
        public ?bool $subscribed,
        public ?string $lastRead,
        public ?int $unreadCount,
        public ?string $parentUserId,
        public bool $isCurrentBot,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromRawData(array $data, bool $isCurrentBot): self
    {
        return new self(
            type: $data['type'],
            userId: $data['user'],
            text: $data['text'],
            ts: $data['ts'],
            threadTs: $data['thread_ts'] ?? null,
            replyCount: $data['reply_count'] ?? null,
            subscribed: $data['subscribed'] ?? null,
            lastRead: $data['last_read'] ?? null,
            unreadCount: $data['unread_count'] ?? null,
            parentUserId: $data['parent_user_id'] ?? null,
            isCurrentBot: $isCurrentBot,
        );
    }
}
