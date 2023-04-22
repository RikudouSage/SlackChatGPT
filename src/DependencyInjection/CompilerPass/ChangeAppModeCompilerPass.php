<?php

namespace App\DependencyInjection\CompilerPass;

use App\Enum\AppMode;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Messenger\Transport\TransportInterface;

final class ChangeAppModeCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $mode = $container->resolveEnvPlaceholders($container->getParameter('app.mode'), true);
        if (!$mode) {
            return;
        }
        assert(is_string($mode));
        $mode = AppMode::from($mode);
        switch ($mode) {
            case AppMode::AwsServerless:
                $container->removeDefinition('Redis');
                $container->removeDefinition('App\Service\RedisBotSettings');
                $container->removeDefinition('App\Service\RedisUserSettings');
                $container->setAlias('cache.app', 'rikudou.dynamo_cache.cache');
                $container->setAlias('App\Service\BotSettings', 'App\Service\DynamoDbBotSettings');
                $container->setAlias('App\Service\UserSettings', 'App\Service\DynamoDbUserSettings');
                break;
            case AppMode::Redis:
                $redisHost = $container->resolveEnvPlaceholders($container->getParameter('app.redis.host'), true);
                assert(is_string($redisHost));

                $container->setAlias('messenger.transport.async', $this->createRedisTransport($redisHost, $container));

                $container->setAlias('app.cache.service', 'cache.app');
                $container->setAlias('App\Service\BotSettings', 'App\Service\RedisBotSettings');
                $container->setAlias('App\Service\UserSettings', 'App\Service\RedisUserSettings');
                $container->removeDefinition('App\Service\DynamoDbBotSettings');
                $container->removeDefinition('App\Service\DynamoDbUserSettings');
                $container->removeDefinition('AsyncAws\DynamoDb\DynamoDbClient');
                $container->removeDefinition('Bref\Symfony\Messenger\Service\Sqs\SqsConsumer');
                $container->removeDefinition('rikudou.dynamo_cache.adapter');
                $container->removeDefinition('rikudou.dynamo_cache.cache');
                $container->removeDefinition('rikudou.dynamo_cache.session');
                $container->removeDefinition('Rikudou\DynamoDbCacheBundle\Cache\DynamoDbCacheAdapter');
                $container->removeDefinition('Rikudou\DynamoDbCacheBundle\Cache\DynamoDbCacheAdapter');
                $container->removeDefinition('Rikudou\DynamoDbCacheBundle\Session\DynamoDbSessionHandler');
                $container->removeDefinition('Rikudou\DynamoDbCache\DynamoDbCache');
                break;
        }
    }

    private function createRedisTransport(string $redisHost, ContainerBuilder $container): string
    {
        $definition = new Definition(TransportInterface::class);
        $definition->setFactory([
            new Reference('messenger.transport.redis.factory'),
            'createTransport',
        ]);
        $definition->addArgument("redis://{$redisHost}/slack_chat_gpt_queue");
        $definition->addArgument([]);
        $definition->addArgument(new Reference('messenger.transport.native_php_serializer'));

        $serviceName = 'app.internal.redis_messenger';
        $container->setDefinition($serviceName, $definition);

        return $serviceName;
    }
}
