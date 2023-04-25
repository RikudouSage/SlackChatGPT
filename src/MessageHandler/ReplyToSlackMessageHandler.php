<?php

namespace App\MessageHandler;

use App\Dto\ChatGptMessage;
use App\Dto\SlackButton;
use App\Dto\SlackButtons;
use App\Dto\SlackConversationReply;
use App\Enum\ChatGptMessageRole;
use App\Message\PostMessageToSlack;
use App\Message\ReplyToSlackMessage;
use App\OpenAi\OpenAiClient;
use App\Service\UserSettings;
use App\Slack\InteractiveMessageHandler\RetrySendMessageHandler;
use App\Slack\SlackApi;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpClient\Exception\TimeoutException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsMessageHandler]
final readonly class ReplyToSlackMessageHandler
{
    public function __construct(
        private SlackApi $slackApi,
        private OpenAiClient $openAiClient,
        private MessageBusInterface $messageBus,
        private UserSettings $userSettings,
        private TranslatorInterface $translator,
        #[Autowire(value: '%app.chatgpt.system_message%')]
        private string $systemMessage,
    ) {
    }

    public function __invoke(ReplyToSlackMessage $newMessage): void
    {
        $messages = [
            new ChatGptMessage(role: ChatGptMessageRole::System, content: $this->systemMessage),
        ];
        if ($parentTs = $newMessage->parentTs) {
            $messages = [...$messages, ...array_map(
                static fn (SlackConversationReply $reply) => ChatGptMessage::fromSlackConversationReply($reply),
                [...$this->slackApi->getConversationReplies($newMessage->channelId, $parentTs, static function (SlackConversationReply $message) use ($newMessage): bool {
                    return $message->text !== $newMessage->message;
                })],
            )];
        }
        $messages[] = new ChatGptMessage(role: ChatGptMessageRole::User, content: $newMessage->message);

        try {
            $gptResponse = $this->openAiClient->getChatResponse($messages, $this->userSettings->getUserApiKey($newMessage->userId));
            $this->messageBus->dispatch(new PostMessageToSlack(
                message: $gptResponse,
                channelId: $newMessage->channelId,
                parentTs: $newMessage->parentTs,
            ));
        } catch (TimeoutException) {
            $this->messageBus->dispatch(new PostMessageToSlack(
                message: $this->translator->trans("Sadly ChatGPT didn't reply in time, maybe their servers are too busy?"),
                channelId: $newMessage->channelId,
                parentTs: $newMessage->threadExists ? $newMessage->parentTs : null,
                ephemeralMessage: true,
                userId: $newMessage->userId,
                buttons: new SlackButtons(
                    id: RetrySendMessageHandler::HANDLED_MESSAGE_NAME,
                    text: $this->translator->trans('Do you want to try again?'),
                    yes: new SlackButton(RetrySendMessageHandler::RESULT_YES, $this->translator->trans('Yes'), json_encode($newMessage, flags: JSON_THROW_ON_ERROR)),
                    no: new SlackButton(RetrySendMessageHandler::RESULT_NO, $this->translator->trans('No'), json_encode($newMessage, flags: JSON_THROW_ON_ERROR)),
                ),
            ));
        }
    }
}
