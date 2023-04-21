<?php

namespace App\Slack;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

interface SlackEndpointValidator
{
    public function supports(Request $request): bool;

    public function getValidationResponse(Request $request): JsonResponse;
}
