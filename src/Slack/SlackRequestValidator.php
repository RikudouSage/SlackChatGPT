<?php

namespace App\Slack;

use Symfony\Component\HttpFoundation\Request;

interface SlackRequestValidator
{
    public function isRequestValid(Request $request): bool;
}
