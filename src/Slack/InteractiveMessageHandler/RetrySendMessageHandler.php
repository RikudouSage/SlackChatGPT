<?php

namespace App\Slack\InteractiveMessageHandler;

use App\Attribute\SlackInteractiveMessageHandler;
use App\Dto\InteractiveCommandEvent;
use App\Message\ReplyToSlackMessage;
use App\Message\RetryReplyToSlackMessage;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[SlackInteractiveMessageHandler(id: RetrySendMessageHandler::HANDLED_MESSAGE_NAME)]
final readonly class RetrySendMessageHandler
{
    public const HANDLED_MESSAGE_NAME = 'retry';

    public const RESULT_YES = 'result-yes';

    public const RESULT_NO = 'result-no';

    public function __construct(
        private TranslatorInterface $translator,
        private MessageBusInterface $messageBus,
    ) {
    }

    /**
     * @param array<string, mixed> $event
     */
    public function __invoke(array $event): ?string
    {
        $event = InteractiveCommandEvent::fromRawData($event);
        $action = $event->actions[array_key_first($event->actions)];
        if ($action->name === self::RESULT_NO) {
            return $this->translator->trans("Ok, I won't retry sending the message.");
        }

        $data = json_decode($action->value, true, flags: JSON_THROW_ON_ERROR);
        assert(is_array($data));

        $this->messageBus->dispatch(new RetryReplyToSlackMessage(
            new ReplyToSlackMessage(
                message: $data['message'],
                channelId: $data['channelId'],
                parentTs: $data['parentTs'],
                userId: $data['userId'],
                threadExists: $data['threadExists'],
            ),
            $event->responseUrl,
        ));

        return $this->translator->trans("Ok, I'll retry sending the message.");
    }
}
