<?php

namespace App\Slack\EventHandler;

use App\Attribute\SlackEventHandler;
use App\Attribute\SlashCommandHandler;
use App\Dto\MessageEvent;
use App\Dto\SlackEvent;
use App\Enum\ChannelMode;
use App\Enum\SlackEventName;
use App\Service\AttributeLocator;
use App\Service\BotSettings;
use App\Slack\CommandHandler\ChangeModeSlashCommandHandler;
use App\Slack\SlackApi;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Translation\TranslatorInterface;

#[SlackEventHandler(eventName: SlackEventName::Message)]
final readonly class SlackUserJoinedMessageHandler
{
    public function __construct(
        private SlackApi $slackApi,
        private BotSettings $settings,
        private TranslatorInterface $translator,
        private AttributeLocator $attributeLocator,
        private ChangeModeSlashCommandHandler $changeModeSlashCommandHandler,
        #[Autowire(value: '%app.bot.default_channel_mode%')]
        private string $defaultMode,
        #[Autowire(value: '%app.bot.welcome_message%')]
        private string $welcomeMessage,
    ) {
    }

    /**
     * @param array<string, mixed> $event
     */
    public function __invoke(array $event): void
    {
        $event = SlackEvent::fromRawData($event);

        if ($event->event->subtype !== 'channel_join') {
            return;
        }
        if ($event->event->userId !== $this->slackApi->getCurrentUserId()) {
            return;
        }
        $channelId = $event->event->channelId;
        $this->settings->setChannelMode($channelId, ChannelMode::from($this->defaultMode));
        $welcomeMessage = $this->welcomeMessage ?: $this->getDefaultWelcomeMessage($event->event);
        $this->slackApi->postMessage(
            $welcomeMessage,
            $channelId,
            null,
        );
    }

    private function getDefaultWelcomeMessage(MessageEvent $event): string
    {
        $result = $this->translator->trans('Hi there!') . ' ';

        $channelId = $event->channelId;
        $mode = $this->settings->getChannelMode($channelId);

        $attribute = $this->attributeLocator->getAttribute($this->changeModeSlashCommandHandler, SlashCommandHandler::class);
        $command = $attribute->name;

        if ($mode === ChannelMode::MentionsOnly) {
            $result .= $this->translator->trans("I'll respond to your messages if you mention me. You can also configure me to respond to every message in this channel by using the slash command '{command}'", [
                '{command}' => "{$command} " . ChannelMode::AllReplies->value,
            ]);
        } else {
            $result .= $this->translator->trans("I'll respond to all your messages in this channel. You can also configure me to respond only to direct mentions by using the slash command '{command}'", [
                '{command}' => "{$command} " . ChannelMode::MentionsOnly->value,
            ]);
        }

        return $result;
    }
}
