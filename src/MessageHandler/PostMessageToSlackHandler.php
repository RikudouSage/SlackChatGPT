<?php

namespace App\MessageHandler;

use App\Message\PostMessageToSlack;
use App\Slack\SlackApi;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class PostMessageToSlackHandler
{
    public function __construct(
        private SlackApi $slackApi,
    ) {
    }

    public function __invoke(PostMessageToSlack $message): void
    {
        $this->slackApi->postMessage($message->message, $message->channelId, $message->parentTs);
    }
}
