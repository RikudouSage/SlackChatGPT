<?php

namespace App\Dto;

final readonly class SlackEvent
{
    /**
     * @param array<Authorization> $authorizations
     */
    public function __construct(
        public string $token,
        public string $teamId,
        public string $contextTeamId,
        public ?string $contextEnterpriseId,
        public string $apiAppId,
        public MessageEvent $event,
        public string $type,
        public string $eventId,
        public string $eventTime,
        public array $authorizations,
        public bool $isExternallySharedChannel,
        public string $eventContext,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromRawData(array $data): self
    {
        assert(is_array($data['event']));
        assert(is_array($data['authorizations']));

        return new self(
            token: $data['token'],
            teamId: $data['team_id'],
            contextTeamId: $data['context_team_id'],
            contextEnterpriseId: $data['context_enterprise_id'],
            apiAppId: $data['api_app_id'],
            event: MessageEvent::fromRawData($data['event']),
            type: $data['type'],
            eventId: $data['event_id'],
            eventTime: $data['event_time'],
            authorizations: array_map(static fn (array $raw) => Authorization::fromRawData($raw), $data['authorizations']),
            isExternallySharedChannel: $data['is_ext_shared_channel'],
            eventContext: $data['event_context'],
        );
    }
}
