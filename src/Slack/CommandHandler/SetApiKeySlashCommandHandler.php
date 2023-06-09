<?php

namespace App\Slack\CommandHandler;

use App\Attribute\SlashCommandHandler;
use App\Dto\SlashCommandEvent;
use App\OpenAi\OpenAiClient;
use App\Service\UserSettings;
use Symfony\Contracts\Translation\TranslatorInterface;

#[SlashCommandHandler(name: '/chatgpt-api-key')]
final readonly class SetApiKeySlashCommandHandler
{
    public function __construct(
        private TranslatorInterface $translator,
        private OpenAiClient $openAiClient,
        private UserSettings $userSettings,
    ) {
    }

    /**
     * @param array<string, string> $event
     */
    public function __invoke(array $event): ?string
    {
        $event = SlashCommandEvent::fromRawData($event);

        if (!trim($event->text)) {
            return $this->translator->trans('Usage: {command} [apiKey] [organizationId]', [
                '{command}' => $event->command,
            ]);
        }

        $text = trim($event->text);
        if ($text === 'remove') {
            $this->userSettings->setUserApiKey($event->userId, null);
            $this->userSettings->setUserOrganizationId($event->userId, null);

            return $this->translator->trans('Your custom api key has been removed.');
        }
        $commands = array_map(static fn (string $item) => trim($item), explode(' ', $text));
        $apiKey = $commands[0];
        $organizationId = $commands[1] ?? ''; // set empty organization ID to use the api key's default instead of workspace default

        if (!$this->openAiClient->isApiKeyValid($apiKey, $organizationId)) {
            return $this->translator->trans('The api key you provided is not valid.');
        }

        $this->userSettings->setUserApiKey($event->userId, $text);
        $this->userSettings->setUserOrganizationId($event->userId, $organizationId);

        return $this->translator->trans("Your api key has been successfully saved and usage limits won't apply to you anymore. To delete it run: {command} remove", [
            '{command}' => $event->command,
        ]);
    }
}
