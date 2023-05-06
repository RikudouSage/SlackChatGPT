<?php

namespace App\Slack\CommandHandler;

use App\Attribute\SlashCommandHandler;
use App\Dto\SlashCommandEvent;
use App\OpenAi\OpenAiClient;
use App\Service\UserSettings;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Translation\TranslatorInterface;

#[SlashCommandHandler(name: '/chatgpt-model')]
final readonly class ChangeModelSlashCommandHandler
{
    public function __construct(
        private TranslatorInterface $translator,
        private OpenAiClient $openAiClient,
        private UserSettings $userSettings,
        #[Autowire('%app.openai.model%')] private string $defaultModel,
    ) {
    }

    /**
     * @param array<string, string> $event
     */
    public function __invoke(array $event): ?string
    {
        $event = SlashCommandEvent::fromRawData($event);
        $availableModels = [...$this->openAiClient->getAvailableModels(
            apiKey: $this->userSettings->getUserApiKey($event->userId),
            organizationId: $this->userSettings->getUserOrganizationId($event->userId),
        )];

        $model = trim($event->text);
        if ($model === 'status') {
            $userModel = $this->userSettings->getUserAiModel($event->userId);
            if ($userModel === null) {
                return $this->translator->trans('You are currently using the default model for this workspace, {model}.', [
                    '{model}' => $this->defaultModel,
                ]);
            }

            return $this->translator->trans('You are currently using {model}, the workspace default is {defaultModel}.', [
                '{model}' => $userModel,
                '{defaultModel}' => $this->defaultModel,
            ]);
        }
        if (!$model) {
            return $this->translator->trans("Usage: {command} [model|status]\nWhere model is one of: {models}", [
                '{command}' => $event->command,
                '{models}' => implode(', ', $availableModels),
            ]);
        }

        if (!in_array($model, $availableModels, true)) {
            return $this->translator->trans("Model '{model}' is not available. You must use one of: {models}", [
                '{model}' => $model,
                '{models}' => implode(', ', $availableModels),
            ]);
        }

        $this->userSettings->setUserAiModel($event->userId, $model);

        return $this->translator->trans('The bot will from now on reply to you using the {model} model.', [
            '{model}' => $model,
        ]);
    }
}
