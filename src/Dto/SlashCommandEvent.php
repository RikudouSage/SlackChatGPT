<?php

namespace App\Dto;

final readonly class SlashCommandEvent
{
    public function __construct(
        public string $token,
        public string $teamId,
        public string $teamDomain,
        public string $channelId,
        public string $channelName,
        public string $userId,
        public string $userName,
        public string $command,
        public string $text,
        public string $apiAppid,
        public bool $isEnterpriseInstall,
        public string $responseUrl,
        public string $triggerId,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromRawData(array $data): self
    {
        return new self(
            token: $data['token'],
            teamId: $data['team_id'],
            teamDomain: $data['team_domain'],
            channelId: $data['channel_id'],
            channelName: $data['channel_name'],
            userId: $data['user_id'],
            userName: $data['user_name'],
            command: $data['command'],
            text: $data['text'],
            apiAppid: $data['api_app_id'],
            isEnterpriseInstall: $data['is_enterprise_install'] === 'true', // wtf slack?
            responseUrl: $data['response_url'],
            triggerId: $data['trigger_id'],
        );
    }
}
