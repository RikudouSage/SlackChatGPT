<?php

namespace App\Enum;

enum ChannelType: string
{
    case PrivateMessage = 'im';
    case PublicChannel = 'channel';
}
