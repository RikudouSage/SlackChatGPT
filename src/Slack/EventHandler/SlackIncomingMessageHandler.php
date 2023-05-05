<?php

namespace App\Slack\EventHandler;

use App\Attribute\SlackEventHandler;
use App\Dto\SlackEvent;
use App\Enum\ChannelMode;
use App\Enum\ChannelType;
use App\Enum\SlackEventName;
use App\Message\PostMessageToSlack;
use App\Message\ReplyToSlackMessage;
use App\Message\ReplyToSlackMessageIfMentionedInThread;
use App\Service\BotSettings;
use App\Service\UserSettings;
use App\Slack\SlackApi;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Contracts\Translation\TranslatorInterface;

#[SlackEventHandler(SlackEventName::Message)]
final readonly class SlackIncomingMessageHandler
{
    public function __construct(
        private SlackApi $slackApi,
        private MessageBusInterface $messageBus,
        private BotSettings $settings,
        private RateLimiterFactory $messagesLimiter,
        private RateLimiterFactory $globalMessagesLimiter,
        private TranslatorInterface $translator,
        private UserSettings $userSettings,
        #[Autowire(value: '%app.rate_limit.global%')]
        private int $globalLimit,
        #[Autowire(value: '%app.rate_limit.per_user%')]
        private int $perUserLimit,
    ) {
    }

    /**
     * @param array<string, mixed> $event
     */
    public function __invoke(array $event): void
    {
        $event = SlackEvent::fromRawData($event);

        if ($event->event->subtype !== null) {
            return;
        }
        if ($event->event->botId === $this->slackApi->getCurrentBotId()) {
            return;
        }

        $channelId = $event->event->channelId;
        $channelMode = $this->settings->getChannelMode($channelId);
        $text = $event->event->text;
        if ($text === null || $event->event->userId === null) {
            return;
        }

        if ($error = $this->getRateLimitError($event)) {
            $this->messageBus->dispatch(new PostMessageToSlack(
                message: $error,
                channelId: $channelId,
                parentTs: $event->event->threadTs ?? $event->event->ts,
            ));

            return;
        }

        $shouldSendReply = false;
        if ($event->event->channelType === ChannelType::PrivateMessage) {
            $shouldSendReply = true;
        } elseif ($channelMode === ChannelMode::AllReplies) {
            $shouldSendReply = true;
        } elseif (str_contains($text, "<@{$this->slackApi->getCurrentUserId()}>")) {
            $shouldSendReply = true;
            $text = str_replace("<@{$this->slackApi->getCurrentUserId()}>", '', $text);
        }

        $replyMessage = new ReplyToSlackMessage(
            message: $text,
            channelId: $channelId,
            parentTs: $event->event->threadTs ?? $event->event->ts,
            userId: $event->event->userId,
            threadExists: $event->event->threadTs !== null,
        );

        if ($shouldSendReply) {
            $this->messageBus->dispatch($replyMessage);
        } else {
            $this->messageBus->dispatch(new ReplyToSlackMessageIfMentionedInThread($replyMessage));
        }
    }

    private function getRateLimitError(SlackEvent $event): ?string
    {
        if ($event->event->userId === null) {
            return null;
        }
        if ($this->userSettings->getUserApiKey($event->event->userId) !== null) {
            return null;
        }
        if ($this->perUserLimit > 0) {
            $limiter = $this->messagesLimiter->create($event->event->userId);
            if (!$limiter->consume()->isAccepted()) {
                return $this->translator->trans("You have reached the daily limit of messages for your account, I sadly won't be responding to any more messages from you.");
            }
        }
        if ($this->globalLimit > 0) {
            $limiter = $this->globalMessagesLimiter->create('global');
            if (!$limiter->consume()->isAccepted()) {
                return $this->translator->trans("The global limit for messages per day has been reached, I sadly won't be responding to any more messages.");
            }
        }

        return null;
    }
}
