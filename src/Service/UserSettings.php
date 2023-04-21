<?php

namespace App\Service;

interface UserSettings
{
    public function getUserApiKey(string $userId): ?string;

    public function setUserApiKey(string $userId, ?string $apiKey): void;
}
