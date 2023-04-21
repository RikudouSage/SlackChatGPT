<?php

namespace App\Dto;

final readonly class Authorization
{
    public function __construct(
        public ?string $enterpriseId,
        public string $teamId,
        public string $userId,
        public bool $isBot,
        public bool $isEnterpriseInstall,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromRawData(array $data): self
    {
        return new self(
            enterpriseId: $data['enterprise_id'],
            teamId: $data['team_id'],
            userId: $data['user_id'],
            isBot: $data['is_bot'],
            isEnterpriseInstall: $data['is_enterprise_install'],
        );
    }
}
