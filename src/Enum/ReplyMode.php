<?php

namespace App\Enum;

enum ReplyMode: string
{
    case Stream = 'stream';
    case AllAtOnce = 'all-at-once';
}
