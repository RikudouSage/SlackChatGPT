<?php

namespace App\Dto;

final readonly class InteractiveCommandEvent
{
    /**
     * @param array<InteractiveCommandEventAction> $actions
     */
    public function __construct(
        public string $type,
        public array $actions,
        public string $callbackId,
        public string $teamId,
        public string $channelId,
        public string $userId,
        public string $actionTs,
        public string $messageTs,
        public string $attachmentId,
        public string $token,
        public string $responseUrl,
        public string $triggerId,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromRawData(array $data): self
    {
        assert(is_array($data['actions']));
        assert(is_array($data['team']));
        assert(is_array($data['channel']));
        assert(is_array($data['user']));

        return new self(
            type: $data['type'],
            actions: array_map(
                static fn (array $action) => new InteractiveCommandEventAction(name: $action['name'], type: $action['type'], value: $action['value']),
                $data['actions']
            ),
            callbackId: $data['callback_id'],
            teamId: $data['team']['id'],
            channelId: $data['channel']['id'],
            userId: $data['user']['id'],
            actionTs: $data['action_ts'],
            messageTs: $data['message_ts'],
            attachmentId: $data['attachment_id'],
            token: $data['token'],
            responseUrl: $data['response_url'],
            triggerId: $data['trigger_id'],
        );
    }
}
