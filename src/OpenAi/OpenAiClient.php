<?php

namespace App\OpenAi;

use App\Dto\ChatGptMessage;
use Symfony\Component\HttpClient\Exception\TimeoutException;

interface OpenAiClient
{
    /**
     * @param array<ChatGptMessage> $messages
     *
     * @throws TimeoutException
     */
    public function getChatResponse(array $messages, ?string $apiKey = null): string;

    public function isApiKeyValid(string $apiKey): bool;

    /**
     * @return iterable<string>
     */
    public function getAvailableModels(?string $apiKey = null): iterable;
}
