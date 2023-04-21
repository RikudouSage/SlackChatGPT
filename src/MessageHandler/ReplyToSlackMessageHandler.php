<?php

namespace App\MessageHandler;

use App\Dto\ChatGptMessage;
use App\Dto\SlackConversationReply;
use App\Enum\ChatGptMessageRole;
use App\Message\PostMessageToSlack;
use App\Message\ReplyToSlackMessage;
use App\OpenAi\OpenAiClient;
use App\Service\UserSettings;
use App\Slack\SlackApi;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
final readonly class ReplyToSlackMessageHandler
{
    public function __construct(
        private SlackApi $slackApi,
        private OpenAiClient $openAiClient,
        private MessageBusInterface $messageBus,
        private UserSettings $userSettings,
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
        $gptResponse = $this->openAiClient->getChatResponse($messages, $this->userSettings->getUserApiKey($newMessage->userId));
        $this->messageBus->dispatch(new PostMessageToSlack(
            message: $gptResponse,
            channelId: $newMessage->channelId,
            parentTs: $newMessage->parentTs,
        ));
    }
}
