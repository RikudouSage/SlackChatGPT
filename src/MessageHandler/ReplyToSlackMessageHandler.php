<?php

namespace App\MessageHandler;

use App\Dto\ChatGptMessage;
use App\Dto\SlackButton;
use App\Dto\SlackButtons;
use App\Dto\SlackConversationReply;
use App\Enum\ChatGptMessageRole;
use App\Enum\ReplyMode;
use App\Exception\ContextTooLongException;
use App\Message\PostMessageToSlack;
use App\Message\ReplyToSlackMessage;
use App\OpenAi\OpenAiClient;
use App\Service\UserSettings;
use App\Slack\InteractiveMessageHandler\RetrySendMessageHandler;
use App\Slack\SlackApi;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpClient\Exception\ClientException;
use Symfony\Component\HttpClient\Exception\TimeoutException;
use Symfony\Component\HttpFoundation\Response;
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
        #[Autowire('%app.bot.reply_mode%')]
        private ReplyMode $replyMode,
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
            if ($this->replyMode === ReplyMode::AllAtOnce) {
                $gptResponse = $this->openAiClient->getChatResponse(
                    messages: $messages,
                    apiKey: $this->userSettings->getUserApiKey($newMessage->userId),
                    model: $this->userSettings->getUserAiModel($newMessage->userId),
                    organizationId: $this->userSettings->getUserOrganizationId($newMessage->userId),
                );

                $this->messageBus->dispatch(new PostMessageToSlack(
                    message: $gptResponse,
                    channelId: $newMessage->channelId,
                    parentTs: $newMessage->parentTs,
                ));
            } else {
                try {
                    $this->handleChunksResponse($this->openAiClient->streamChatResponse(
                        messages: $messages,
                        apiKey: $this->userSettings->getUserApiKey($newMessage->userId),
                        model: $this->userSettings->getUserAiModel($newMessage->userId),
                        organizationId: $this->userSettings->getUserOrganizationId($newMessage->userId),
                    ), $newMessage);
                } catch (ContextTooLongException) {
                    $this->messageBus->dispatch(new PostMessageToSlack(
                        message: $this->translator->trans("This conversation is over GPT's token limit, please start a new one."),
                        channelId: $newMessage->channelId,
                        parentTs: $newMessage->threadExists ? $newMessage->parentTs: null,
                        ephemeralMessage: true,
                        userId: $newMessage->userId,
                    ));
                }
            }
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
        } catch (ClientException $e) {
            if ($e->getCode() === Response::HTTP_UNAUTHORIZED) {
                if ($this->userSettings->getUserApiKey($newMessage->userId) !== null) {
                    $message = $this->translator->trans("You're using a custom api key that is not valid, please provide a new one or switch to using the globally configured one.");
                } else {
                    $message = $this->translator->trans("The api key this workspace is using is not valid, please contact your administrator.");
                }
                $this->messageBus->dispatch(new PostMessageToSlack(
                    message: $message,
                    channelId: $newMessage->channelId,
                    parentTs: $newMessage->threadExists ? $newMessage->parentTs : null,
                    ephemeralMessage: true,
                    userId: $newMessage->userId,
                ));
            } else {
                throw $e;
            }
        }
    }

    /**
     * @param iterable<string> $chunks
     */
    private function handleChunksResponse(iterable $chunks, ReplyToSlackMessage $newMessage): void
    {
        $interval = 1.5;

        $ts = null;
        $fullText = '';
        $lastTick = 0;
        foreach ($chunks as $chunk) {
            $fullText .= $chunk;
            $now = microtime(true);
            if ($now - $lastTick > $interval) {
                if ($ts === null) {
                    $ts = $this->slackApi->postMessage("{$fullText}...", $newMessage->channelId, $newMessage->parentTs);
                } else {
                    $this->slackApi->updateMessage("{$fullText}...", $ts, $newMessage->channelId);
                }
                $lastTick = microtime(true);
            }
        }
        if ($ts) {
            $this->slackApi->updateMessage($fullText, $ts, $newMessage->channelId);
        } else {
            $this->messageBus->dispatch(new PostMessageToSlack(
                message: $fullText,
                channelId: $newMessage->channelId,
                parentTs: $newMessage->parentTs,
            ));
        }
    }
}
