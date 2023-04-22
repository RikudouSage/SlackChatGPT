<?php

namespace App\Slack;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;

final readonly class DefaultSlackRequestValidator implements SlackRequestValidator
{
    public function __construct(
        #[Autowire(value: '%app.signing_secret%')] private string $slackSigningSecret,
        #[Autowire(value: '%app.slack.validate_signature%')] private bool $validateSignatureEnabled,
    ) {
    }

    public function isRequestValid(Request $request): bool
    {
        if (!$this->validateSignatureEnabled) {
            return true;
        }
        $content = $request->getContent() ?: http_build_query($request->request->all());

        $version = 'v0';
        $timestamp = $request->headers->get('X-Slack-Request-Timestamp');
        $signature = $request->headers->get('X-Slack-Signature');

        $baseString = "{$version}:{$timestamp}:{$content}";
        $hash = "{$version}=" . hash_hmac('sha256', $baseString, $this->slackSigningSecret);

        return $hash === $signature;
    }
}
