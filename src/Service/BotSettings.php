<?php

namespace App\Service;

use App\Enum\ChannelMode;

interface BotSettings
{
    public function setChannelMode(string $channelId, ChannelMode $mode): void;

    public function getChannelMode(string $channelId): ChannelMode;
}
