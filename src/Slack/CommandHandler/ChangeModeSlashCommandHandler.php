<?php

namespace App\Slack\CommandHandler;

use App\Attribute\SlashCommandHandler;
use App\Dto\SlashCommandEvent;
use App\Enum\ChannelMode;
use App\Message\PostMessageToSlack;
use App\Service\BotSettings;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[SlashCommandHandler('/chatgpt')]
final readonly class ChangeModeSlashCommandHandler
{
    public function __construct(
        private BotSettings $settings,
        private MessageBusInterface $messageBus,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @param array<string, string> $event
     */
    public function __invoke(array $event): ?string
    {
        $event = SlashCommandEvent::fromRawData($event);
        $channelId = $event->channelId;
        $currentMode = $this->settings->getChannelMode($channelId);

        if (!trim($event->text)) {
            return $this->translator->trans("You must provide the mode to change to, either '{mode1}' or '{mode2}'. The current mode is '{currentMode}'.", [
                '{mode1}' => ChannelMode::AllReplies->value,
                '{mode2}' => ChannelMode::MentionsOnly->value,
                '{currentMode}' => $currentMode->value,
            ]);
        }

        $mode = ChannelMode::tryFrom($event->text);
        if ($mode === null) {
            return $this->translator->trans("Invalid argument '{argument}', it must be '{mode1}' or '{mode2}'", [
                '{mode1}' => ChannelMode::AllReplies->value,
                '{mode2}' => ChannelMode::MentionsOnly->value,
                '{argument}' => $event->text,
            ]);
        }

        if ($mode === $currentMode) {
            return $this->translator->trans("The mode was already set to '{mode}'", [
                '{mode}' => $mode->value,
            ]);
        }

        $this->settings->setChannelMode($channelId, $mode);

        $messageCommon = $this->translator->trans('The response mode has been changed by {user}.', [
            '{user}' => "<@{$event->userId}>",
        ]);
        $messageAll = $this->translator->trans('I will now respond to all messages.');
        $messageMentions = $this->translator->trans("I will now only respond to direct @ mentions and threads I'm already mentioned in.");
        $this->messageBus->dispatch(new PostMessageToSlack(
            message: "{$messageCommon} " . ($mode === ChannelMode::AllReplies ? $messageAll : $messageMentions),
            channelId: $channelId,
            parentTs: null,
        ));

        return null;
    }
}
