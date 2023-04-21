<?php

namespace App\Controller;

use App\Attribute\SlackEventHandler;
use App\Attribute\SlashCommandHandler;
use App\Service\AttributeLocator;
use App\Slack\SlackEndpointValidator;
use App\Slack\SlackRequestValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

final class SlackController extends AbstractController
{
    /**
     * @param iterable<callable&object> $eventHandlers
     * @param iterable<callable&object> $commandHandlers
     */
    public function __construct(
        #[TaggedIterator('app.slack.event_handler')]
        private readonly iterable $eventHandlers,
        #[TaggedIterator('app.slack.slash_command_handler')]
        private readonly iterable $commandHandlers,
    ) {
    }

    #[Route('/slack/events', name: 'app.slack.events')]
    #[Route('/{_locale}/slack/events', name: 'app.slack.events.locale')]
    public function events(
        SlackRequestValidator $requestValidator,
        SlackEndpointValidator $endpointValidator,
        AttributeLocator $attributeLocator,
        Request $request,
    ): JsonResponse {
        if ($endpointValidator->supports($request)) {
            return $endpointValidator->getValidationResponse($request);
        }
        if (!$requestValidator->isRequestValid($request)) {
            throw new BadRequestHttpException('The request has an invalid signature.');
        }

        $hasHandlers = false;
        $json = json_decode($request->getContent(), true, flags: JSON_THROW_ON_ERROR);
        assert(is_array($json));

        foreach ($this->eventHandlers as $eventHandler) {
            $attribute = $attributeLocator->getAttribute($eventHandler, SlackEventHandler::class);
            if ($json['event']['type'] !== $attribute->eventName->value) {
                continue;
            }
            $hasHandlers = true;
            $eventHandler($json);
        }

        if (!$hasHandlers) {
            error_log((string) json_encode([
                'error' => 'No handler found for event',
                'event' => $json,
            ]));
        }

        return new JsonResponse(status: Response::HTTP_OK);
    }

    #[Route('/slack/commands', name: 'app.slack.commands')]
    #[Route('/{_locale}/slack/commands', name: 'app.slack.commands.locale')]
    public function commands(
        SlackRequestValidator $requestValidator,
        AttributeLocator $attributeLocator,
        TranslatorInterface $translator,
        Request $request,
    ): Response {
        if (!$requestValidator->isRequestValid($request)) {
            throw new BadRequestHttpException('The request has an invalid signature.');
        }

        $hasHandlers = false;
        $data = $request->request->all();
        foreach ($this->commandHandlers as $commandHandler) {
            $attribute = $attributeLocator->getAttribute($commandHandler, SlashCommandHandler::class);
            if ($data['command'] !== $attribute->name) {
                continue;
            }
            $hasHandlers = true;
            $response = $commandHandler($data);
            if ($response !== null) {
                return new JsonResponse([
                    'response_type' => 'ephemeral',
                    'text' => $response,
                ]);
            }
        }

        if (!$hasHandlers) {
            error_log((string) json_encode([
                'error' => 'No handler found for command',
                'command' => $data,
            ]));

            return new JsonResponse([
                'response_type' => 'ephemeral',
                'text' => $translator->trans("Sorry, we don't know how to handle that command."),
            ]);
        }

        return new Response(status: Response::HTTP_OK);
    }
}
