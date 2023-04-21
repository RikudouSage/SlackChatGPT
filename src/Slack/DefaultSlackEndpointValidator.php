<?php

namespace App\Slack;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class DefaultSlackEndpointValidator implements SlackEndpointValidator
{
    public function supports(Request $request): bool
    {
        $content = json_decode($request->getContent(), true, flags: JSON_THROW_ON_ERROR);
        assert(is_array($content));

        return ($content['type'] ?? null) === 'url_verification';
    }

    public function getValidationResponse(Request $request): JsonResponse
    {
        $content = json_decode($request->getContent(), true, flags: JSON_THROW_ON_ERROR);
        assert(is_array($content));

        return new JsonResponse([
            'challenge' => $content['challenge'],
        ]);
    }
}
