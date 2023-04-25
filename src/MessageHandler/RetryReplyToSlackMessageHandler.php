<?php

namespace App\MessageHandler;

use App\Message\RetryReplyToSlackMessage;
use App\Slack\SlackApi;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsMessageHandler]
final readonly class RetryReplyToSlackMessageHandler
{
    public function __construct(
        private SlackApi $slackApi,
        private MessageBusInterface $messageBus,
        private TranslatorInterface $translator,
    ) {
    }

    public function __invoke(RetryReplyToSlackMessage $event): void
    {
        $this->messageBus->dispatch($event->message);
        $this->slackApi->postEphemeralReply(
            $event->responseUrl,
            text: $this->translator->trans('Your message has been sent again!'),
        );
    }
}
