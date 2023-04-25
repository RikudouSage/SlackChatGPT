<?php

namespace App\OpenAi;

use App\Dto\ChatGptMessage;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class DefaultOpenAiClient implements OpenAiClient
{
    public function __construct(
        private HttpClientInterface $httpClient,
        #[Autowire('%app.openai_api_key%')] private string $apiKey,
        #[Autowire('%app.openai.timeout%')] private int $timeout,
    ) {
    }

    public function getChatResponse(array $messages, ?string $apiKey = null): string
    {
        $apiKey ??= $this->apiKey;

        $response = $this->httpClient->request(Request::METHOD_POST, 'https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => "Bearer {$apiKey}",
            ],
            'json' => [
                'model' => 'gpt-3.5-turbo',
                'messages' => array_map(
                    static fn (ChatGptMessage $message) => ['role' => $message->role->value, 'content' => $message->content],
                    $messages,
                ),
            ],
            'timeout' => $this->timeout,
        ]);

        $json = json_decode($response->getContent(), true, flags: JSON_THROW_ON_ERROR);
        assert(is_array($json));

        return $json['choices'][0]['message']['content'];
    }

    public function isApiKeyValid(string $apiKey): bool
    {
        return $this->httpClient->request(Request::METHOD_GET, 'https://api.openai.com/v1/models', [
            'headers' => [
                'Authorization' => "Bearer {$apiKey}",
            ],
        ])->getStatusCode() === Response::HTTP_OK;
    }
}
