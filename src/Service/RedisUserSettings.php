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
        $apikey = $this->redis->get("slack_chat_gpt::custom_api_key::{$userId}");
        if ($apikey === false || $apikey === null) {
            return null;
        }
        assert(is_string($apikey));

        return $apikey;
    }

    public function setUserApiKey(string $userId, ?string $apiKey): void
    {
        $this->redis->set("slack_chat_gpt::custom_api_key::{$userId}", $apiKey);
    }
}
