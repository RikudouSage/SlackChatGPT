<?php

namespace App\Enum;

enum ChannelMode: string
{
    case AllReplies = 'all';
    case MentionsOnly = 'mentions';
}
