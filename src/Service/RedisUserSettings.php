<?php

namespace App\Service;

use Redis;

final readonly class RedisUserSettings implements UserSettings
{
    public function __construct(
        private Redis $redis,
    ) {
    }

    public function getUserApiKey(string $userId): ?string
    {
        return $this->getString($userId, 'custom_api_key');
    }

    public function setUserApiKey(string $userId, ?string $apiKey): void
    {
        $this->setString($userId, 'custom_api_key', $apiKey);
    }

    public function getUserOrganizationId(string $userId): ?string
    {
        return $this->getString($userId, 'organization_id');
    }

    public function getUserAiModel(string $userId): ?string
    {
        return $this->getString($userId, 'ai_model');
    }

    public function setUserOrganizationId(string $userId, ?string $organizationId): void
    {
        $this->setString($userId, 'organization_id', $organizationId);
    }

    public function setUserAiModel(string $userId, ?string $aiModel): void
    {
        $this->setString($userId, 'ai_model', $aiModel);
    }

    private function getString(string $userId, string $key): ?string
    {
        $value = $this->redis->get("slack_chat_gpt::{$key}::{$userId}");
        if ($value === false || $value === null) {
            return null;
        }
        assert(is_string($value));

        return $value;
    }

    private function setString(string $userId, string $key, ?string $value): void
    {
        $fullKey = "slack_chat_gpt::{$key}::{$userId}";
        if ($value === null) {
            $this->redis->del($fullKey);

            return;
        }
        $this->redis->set($fullKey, $value);
    }
}
