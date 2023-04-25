<?php

namespace App\MessageHandler;

use App\Message\PostMessageToSlack;
use App\Slack\SlackApi;
use LogicException;
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
        if ($message->ephemeralMessage) {
            if ($message->userId === null) {
                throw new LogicException('You must provide a user id when posting an ephemeral message.');
            }
            $this->slackApi->postEphemeralMessage($message->message, $message->channelId, $message->userId, $message->parentTs, $message->buttons);
        } else {
            $this->slackApi->postMessage($message->message, $message->channelId, $message->parentTs, $message->buttons);
        }
    }
}
