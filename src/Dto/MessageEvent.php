<?php

namespace App\Dto;

use App\Enum\ChannelType;

final readonly class MessageEvent
{
    public function __construct(
        public ?string $clientMessageId,
        public string $type,
        public ?string $subtype,
        public ?string $text,
        public ?string $userId,
        public ?string $botId,
        public ?string $threadTs,
        public string $ts,
        public ?string $teamId,
        public string $channelId,
        public string $eventTs,
        public ChannelType $channelType,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromRawData(array $data): self
    {
        return new self(
            clientMessageId: $data['client_msg_id'] ?? null,
            type: $data['type'],
            subtype: $data['subtype'] ?? null,
            text: $data['text'] ?? null,
            userId: $data['user'] ?? null,
            botId: $data['bot_id'] ?? null,
            threadTs: $data['thread_ts'] ?? null,
            ts: $data['ts'],
            teamId: $data['team'] ?? null,
            channelId: $data['channel'],
            eventTs: $data['event_ts'],
            channelType: ChannelType::from($data['channel_type']),
        );
    }
}
