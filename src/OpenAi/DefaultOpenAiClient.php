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

    public function getChatResponse(
        array $messages,
        ?string $apiKey = null,
        ?string $model = null,
        ?string $organizationId = null
    ): string {
        return implode('', [...$this->streamChatResponse($messages, $apiKey, $model, $organizationId)]);
    }

    public function streamChatResponse(
        array $messages,
        ?string $apiKey = null,
        ?string $model = null,
        ?string $organizationId = null
    ): iterable {
        $apiKey ??= $this->apiKey;
        $model ??= $this->model;
        $organizationId ??= $this->organizationId;

        $headers = [
            'Authorization' => "Bearer {$apiKey}",
        ];
        if ($organizationId) {
            $headers['OpenAI-Organization'] = $organizationId;
        }
        $response = $this->httpClient->request(Request::METHOD_POST, 'https://api.openai.com/v1/chat/completions', [
            'headers' => $headers,
            'json' => [
                'model' => $model,
                'messages' => array_map(
                    static fn (ChatGptMessage $message) => ['role' => $message->role->value, 'content' => $message->content],
                    $messages,
                ),
                'stream' => true,
            ],
            'timeout' => $this->timeout,
        ]);
        $chunks = $this->httpClient->stream($response);
        foreach ($chunks as $chunk) {
            $content = trim(substr($chunk->getContent(), strlen('data: ')));
            if (!$content) {
                continue;
            }

            $parts = explode('data: ', $content);
            foreach ($parts as $part) {
                $part = trim($part);
                if ($part === '[DONE]') {
                    break 2;
                }
                $json = json_decode($part, true, flags: JSON_THROW_ON_ERROR);
                assert(is_array($json));
                $text = $json['choices'][0]['delta']['content'] ?? null;
                if ($text === null) {
                    continue;
                }

                yield $text;
            }
        }
    }

    public function isApiKeyValid(string $apiKey, ?string $organizationId = null): bool
    {
        $organizationId ??= $this->organizationId;
        $headers = [
            'Authorization' => "Bearer {$apiKey}",
        ];
        if ($organizationId) {
            $headers['OpenAI-Organization'] = $organizationId;
        }

        return $this->httpClient->request(Request::METHOD_GET, 'https://api.openai.com/v1/models', [
            'headers' => $headers,
        ])->getStatusCode() === Response::HTTP_OK;
    }

    public function getAvailableModels(?string $apiKey = null, ?string $organizationId = null): iterable
    {
        $apiKey ??= $this->apiKey;
        $organizationId ??= $this->organizationId;

        $headers = [
            'Authorization' => "Bearer {$apiKey}",
        ];
        if ($organizationId) {
            $headers['OpenAI-Organization'] = $organizationId;
        }

        // filter for models that have chat capability, until OpenAI adds some endpoint, this is the only way sadly
        /** @var array<(callable(string $modelName): bool)> $filters */
        $filters = [
            static fn (string $modelName) => str_starts_with($modelName, 'gpt'),
        ];

        $response = $this->httpClient->request(Request::METHOD_GET, 'https://api.openai.com/v1/models', [
            'headers' => $headers,
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
