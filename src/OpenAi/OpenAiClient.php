<?php

namespace App\OpenAi;

use App\Dto\ChatGptMessage;

interface OpenAiClient
{
    /**
     * @param array<ChatGptMessage> $messages
     */
    public function getChatResponse(array $messages, ?string $apiKey = null): string;

    public function isApiKeyValid(string $apiKey): bool;
}
