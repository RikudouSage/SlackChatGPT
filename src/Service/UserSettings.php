<?php

namespace App\Service;

interface UserSettings
{
    public function getUserApiKey(string $userId): ?string;

    public function getUserOrganizationId(string $userId): ?string;

    public function getUserAiModel(string $userId): ?string;

    public function setUserApiKey(string $userId, ?string $apiKey): void;

    public function setUserOrganizationId(string $userId, ?string $organizationId): void;

    public function setUserAiModel(string $userId, ?string $aiModel): void;
}
