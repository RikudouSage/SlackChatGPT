<?php

namespace App;

use App\Attribute\SlackEventHandler;
use App\Attribute\SlashCommandHandler;
use App\DependencyInjection\CompilerPass\ChangeAppModeCompilerPass;
use Bref\SymfonyBridge\BrefKernel;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class Kernel extends BrefKernel
{
    use MicroKernelTrait;

    protected function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new ChangeAppModeCompilerPass());
        $container->registerAttributeForAutoconfiguration(
            SlackEventHandler::class,
            static function (ChildDefinition $definition, SlackEventHandler $attribute): void {
                $definition->addTag('app.slack.event_handler', ['event' => $attribute->eventName->value]);
            },
        );
        $container->registerAttributeForAutoconfiguration(
            SlashCommandHandler::class,
            static function (ChildDefinition $definition, SlashCommandHandler $attribute): void {
                $definition->addTag('app.slack.slash_command_handler', ['command' => $attribute->name]);
            }
        );
    }

    public function getBuildDir(): string
    {
        return $this->getProjectDir() . '/var/cache/' . $this->environment;
    }
}
