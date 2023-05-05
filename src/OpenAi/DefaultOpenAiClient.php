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
        #[Autowire('%app.openai.model%')] private string $model,
        #[Autowire('%app.openai.organization_id%')] private string $organizationId,
    ) {
    }

    public function getChatResponse(array $messages, ?string $apiKey = null): string
    {
        $apiKey ??= $this->apiKey;

        $headers = [
            'Authorization' => "Bearer {$apiKey}",
        ];
        if ($this->organizationId) {
            $headers['OpenAI-Organization'] = $this->organizationId;
        }
        $response = $this->httpClient->request(Request::METHOD_POST, 'https://api.openai.com/v1/chat/completions', [
            'headers' => $headers,
            'json' => [
                'model' => $this->model,
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

    public function getAvailableModels(?string $apiKey = null): iterable
    {
        $apiKey ??= $this->apiKey;

        // filter for models that have chat capability, until OpenAI adds some endpoint, this is the only way sadly
        /** @var array<(callable(string $modelName): bool)> $filters */
        $filters = [
            static fn (string $modelName) => str_starts_with($modelName, 'gpt'),
        ];

        $response = $this->httpClient->request(Request::METHOD_GET, 'https://api.openai.com/v1/models', [
            'headers' => [
                'Authorization' => "Bearer {$apiKey}",
            ],
        ]);
        $json = json_decode($response->getContent(), true, flags: JSON_THROW_ON_ERROR);
        assert(is_array($json));

        foreach ($json['data'] as $model) {
            $modelName = $model['id'];
            assert(is_string($modelName));

            foreach ($filters as $filter) {
                if (!$filter($modelName)) {
                    continue 2;
                }
            }

            yield $modelName;
        }
    }
}
