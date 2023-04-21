<?php

namespace App\Controller;

use App\Enum\ChannelMode;
use App\Slack\SlackApi;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

final class HomepageController extends AbstractController
{
    #[Route('/', name: 'app.status')]
    public function status(
        #[Autowire(value: '%app.openai_api_key%')] string $openAiApiKey,
        #[Autowire(value: '%app.dynamo.channel_mode_table%')] string $dynamoTable,
        #[Autowire(value: '%app.chatgpt.system_message%')] string $systemMessage,
        #[Autowire(value: '%app.bot_token%')] string $botToken,
        #[Autowire(value: '%app.signing_secret%')] string $signingSecret,
        #[Autowire(value: '%app.bot.default_channel_mode%')] string $channelMode,
        SlackApi $slackApi,
    ): JsonResponse {
        $hasOpenAiApiKey = !!$openAiApiKey;
        $hasDynamoTable = !!$dynamoTable;
        $hasSystemMessage = !!$systemMessage;
        $hasBotToken = !!$botToken;
        $hasSigningSecret = !!$signingSecret;
        $channelModeValid = ChannelMode::tryFrom($channelMode) !== null;
        $slackTokenValid = false;

        if ($hasBotToken) {
            try {
                $slackApi->getCurrentUserId();
                $slackTokenValid = true;
            } catch (Throwable) {
                // ignore
            }
        }

        $allOk = $hasOpenAiApiKey && $hasDynamoTable && $hasSystemMessage && $hasBotToken && $hasSigningSecret && $slackTokenValid && $channelModeValid;

        return new JsonResponse([
            'status' => $allOk ? 'ok' : 'error',
            'details' => [
                'openAiApiKey' => $hasOpenAiApiKey ? 'ok' : 'error',
                'channelModeDynamoDbTable' => $hasDynamoTable ? 'ok' : 'error',
                'gptModelSystemMessage' => $hasSystemMessage ? 'ok' : 'error',
                'slackBotToken' => $hasBotToken ? 'ok' : 'error',
                'slackBotTokenValid' => $slackTokenValid ? 'ok' : 'error',
                'slackSigningSecret' => $hasSigningSecret ? 'ok' : 'error',
                'defaultChannelModeValid' => $channelModeValid ? 'ok' : 'error',
            ],
        ], $allOk ? Response::HTTP_OK : Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
