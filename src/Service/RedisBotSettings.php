<?php

namespace App\Service;

use App\Enum\ChannelMode;
use Redis;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class RedisBotSettings implements BotSettings
{
    public function __construct(
        private Redis $redis,
        #[Autowire(value: '%app.bot.default_channel_mode%')]
        private string $defaultChannelMode,
    ) {
    }

    public function setChannelMode(string $channelId, ChannelMode $mode): void
    {
        $this->redis->set("slack_chat_gpt::channel_mode::{$channelId}", $mode->value);
    }

    public function getChannelMode(string $channelId): ChannelMode
    {
        $mode = $this->redis->get("slack_chat_gpt::channel_mode::{$channelId}");
        assert(is_string($mode) || $mode === false);
        if ($mode === false) {
            $mode = $this->defaultChannelMode;
        }

        return ChannelMode::from($mode);
    }
}
