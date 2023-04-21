<?php

namespace App\MessageHandler;

use App\Message\ReplyToSlackMessage;
use App\Message\ReplyToSlackMessageIfMentionedInThread;
use App\Slack\SlackApi;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
final readonly class ReplyToSlackMessageIfMentionedInThreadHandler
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private SlackApi $slackApi,
    ) {
    }

    public function __invoke(ReplyToSlackMessageIfMentionedInThread $event): void
    {
        assert($event->message->parentTs !== null);
        $messages = $this->slackApi->getConversationReplies(
            $event->message->channelId,
            $event->message->parentTs,
        );
        foreach ($messages as $message) {
            if ($message->isCurrentBot || str_contains($message->text, "<@{$this->slackApi->getCurrentUserId()}>")) {
                $this->messageBus->dispatch(new ReplyToSlackMessage(
                    message: str_replace("<@{$this->slackApi->getCurrentUserId()}>", '', $event->message->message),
                    channelId: $event->message->channelId,
                    parentTs: $event->message->parentTs,
                    userId: $event->message->userId,
                ));
                break;
            }
        }
    }
}
