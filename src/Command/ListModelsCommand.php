<?php

namespace App\Command;

use App\OpenAi\OpenAiClient;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand('app:openai:models')]
final class ListModelsCommand extends Command
{
    public function __construct(
        private readonly OpenAiClient $openAiClient,
        #[Autowire('%app.openai.model%')] private readonly string $selectedModel,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Lists models available for your configuration.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $models = $this->openAiClient->getAvailableModels();

        $io->table(
            ['Model'],
            array_map(
                fn (string $name) => [$name === $this->selectedModel ? "✅️ {$name}" : $name],
                [...$models],
            )
        );

        return Command::SUCCESS;
    }
}
