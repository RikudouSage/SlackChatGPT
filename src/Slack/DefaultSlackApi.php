<?php

namespace App\Slack;

use App\Dto\SlackButtons;
use App\Dto\SlackConversationReply;
use DateInterval;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class DefaultSlackApi implements SlackApi
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private CacheItemPoolInterface $cache,
        #[Autowire('%app.bot_token%')] private string $token,
    ) {
    }

    public function getCurrentBotId(): string
    {
        $cacheItem = $this->cache->getItem('app.slack.bot_id');
        if ($cacheItem->isHit()) {
            $result = $cacheItem->get();
            assert(is_string($result));

            return $result;
        }

        $response = $this->httpClient->request(Request::METHOD_GET, 'https://slack.com/api/auth.test', [
            'headers' => [
                'Authorization' => "Bearer {$this->token}",
            ],
        ]);

        $json = json_decode($response->getContent(), true, flags: JSON_THROW_ON_ERROR);
        assert(is_array($json));

        $botId = $json['bot_id'];
        $cacheItem->set($botId);
        $cacheItem->expiresAfter(new DateInterval('PT10M'));
        $this->cache->save($cacheItem);

        return $botId;
    }

    public function getConversationReplies(string $channelId, string $parentTs, ?callable $filter = null): iterable
    {
        $filter ??= static fn (SlackConversationReply $message) => true;
        $response = $this->httpClient->request(Request::METHOD_POST, 'https://slack.com/api/conversations.replies', [
            'headers' => [
                'Authorization' => "Bearer {$this->token}",
            ],
            'body' => [
                'channel' => $channelId,
                'ts' => $parentTs,
            ],
        ]);

        $botId = $this->getCurrentBotId();
        $json = json_decode($response->getContent(), true, flags: JSON_THROW_ON_ERROR);
        assert(is_array($json));

        foreach ($json['messages'] as $message) {
            $message = SlackConversationReply::fromRawData($message, ($message['bot_id'] ?? null) === $botId, );
            if (!$filter($message)) {
                continue;
            }

            yield $message;
        }
    }

    public function postMessage(string $text, string $channelId, ?string $parentTs, ?SlackButtons $buttons = null): void
    {
        $json = [
            'channel' => $channelId,
            'text' => $text,
        ];
        if ($parentTs !== null) {
            $json['thread_ts'] = $parentTs;
        }

        if ($buttons !== null && count($buttons->buttons)) {
            $json['attachments'] = [];
            $attachment = [
                'text' => $buttons->text,
                'callback_id' => $buttons->id,
                'attachment_type' => 'default',
                'actions' => [],
            ];
            if ($buttons->color !== null) {
                $attachment['color'] = $buttons->color;
            }
            foreach ($buttons->buttons as $button) {
                $attachment['actions'][] = [
                    'name' => $button->id,
                    'text' => $button->text,
                    'type' => 'button',
                    'value' => $button->value,
                ];
            }
            $json['attachments'][] = $attachment;
        }

        $this->httpClient->request(Request::METHOD_POST, 'https://slack.com/api/chat.postMessage', [
            'headers' => [
                'Authorization' => "Bearer {$this->token}",
            ],
            'json' => $json,
        ]);
    }

    public function postEphemeralMessage(string $text, string $channelId, string $userId, ?string $parentTs, ?SlackButtons $buttons = null): void
    {
        $json = [
            'channel' => $channelId,
            'text' => $text,
            'user' => $userId,
        ];
        if ($parentTs !== null) {
            $json['thread_ts'] = $parentTs;
        }

        if ($buttons !== null && count($buttons->buttons)) {
            $json['attachments'] = [];
            $attachment = [
                'text' => $buttons->text,
                'callback_id' => $buttons->id,
                'attachment_type' => 'default',
                'actions' => [],
            ];
            if ($buttons->color !== null) {
                $attachment['color'] = $buttons->color;
            }
            foreach ($buttons->buttons as $button) {
                $attachment['actions'][] = [
                    'name' => $button->id,
                    'text' => $button->text,
                    'type' => 'button',
                    'value' => $button->value,
                ];
            }
            $json['attachments'][] = $attachment;
        }

        $this->httpClient->request(Request::METHOD_POST, 'https://slack.com/api/chat.postEphemeral', [
            'headers' => [
                'Authorization' => "Bearer {$this->token}",
            ],
            'json' => $json,
        ]);
    }

    public function getCurrentUserId(): string
    {
        $cacheItem = $this->cache->getItem('app.slack.bot_user_id');
        if ($cacheItem->isHit()) {
            $result = $cacheItem->get();
            assert(is_string($result));

            return $result;
        }

        $response = $this->httpClient->request(Request::METHOD_GET, 'https://slack.com/api/auth.test', [
            'headers' => [
                'Authorization' => "Bearer {$this->token}",
            ],
        ]);

        $json = json_decode($response->getContent(), true, flags: JSON_THROW_ON_ERROR);
        assert(is_array($json));

        $userId = $json['user_id'];
        $cacheItem->set($userId);
        $cacheItem->expiresAfter(new DateInterval('PT10M'));
        $this->cache->save($cacheItem);

        return $userId;
    }

    public function postEphemeralReply(string $responseUrl, string $text = '', bool $replaceOriginal = true, bool $deleteOriginal = false): void
    {
        $this->httpClient->request(Request::METHOD_POST, $responseUrl, [
            'headers' => [
                'Authorization' => "Bearer {$this->token}",
            ],
            'json' => [
                'response_type' => 'ephemeral',
                'text' => $text,
                'replace_original' => $replaceOriginal,
                'delete_original' => $deleteOriginal,
            ],
        ]);
    }
}
