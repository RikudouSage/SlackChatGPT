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
    public function getChatResponse(
        array $messages,
        ?string $apiKey = null,
        ?string $model = null,
        ?string $organizationId = null,
    ): string;

    /**
     * @param array<ChatGptMessage> $messages
     *
     * @throws TimeoutException
     *
     * @return iterable<string>
     */
    public function streamChatResponse(
        array $messages,
        ?string $apiKey = null,
        ?string $model = null,
        ?string $organizationId = null,
    ): iterable;

    public function isApiKeyValid(string $apiKey, ?string $organizationId = null): bool;

    /**
     * @return iterable<string>
     */
    public function getAvailableModels(?string $apiKey = null, ?string $organizationId = null): iterable;
}
