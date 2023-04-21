<?php

namespace App\Enum;

enum ChatGptMessageRole: string
{
    case System = 'system';
    case User = 'user';
    case ChatGpt = 'assistant';
}
