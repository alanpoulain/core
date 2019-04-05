<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ApiPlatform\Core\Bridge\Symfony\Bundle\EventSubscriber;

use ApiPlatform\Core\Event\DeserializeEvent;
use ApiPlatform\Core\Event\FormatAddEvent;
use ApiPlatform\Core\Event\PostDeserializeEvent;
use ApiPlatform\Core\Event\PostReadEvent;
use ApiPlatform\Core\Event\PostRespondEvent;
use ApiPlatform\Core\Event\PostSerializeEvent;
use ApiPlatform\Core\Event\PostValidateEvent;
use ApiPlatform\Core\Event\PostValidationExceptionEvent;
use ApiPlatform\Core\Event\PostWriteEvent;
use ApiPlatform\Core\Event\PreDeserializeEvent;
use ApiPlatform\Core\Event\PreReadEvent;
use ApiPlatform\Core\Event\PreRespondEvent;
use ApiPlatform\Core\Event\PreSerializeEvent;
use ApiPlatform\Core\Event\PreValidateEvent;
use ApiPlatform\Core\Event\PreValidationExceptionEvent;
use ApiPlatform\Core\Event\PreWriteEvent;
use ApiPlatform\Core\Event\QueryParameterValidateEvent;
use ApiPlatform\Core\Event\ReadEvent;
use ApiPlatform\Core\Event\RespondEvent;
use ApiPlatform\Core\Event\SerializeEvent;
use ApiPlatform\Core\Event\ValidateEvent;
use ApiPlatform\Core\Event\ValidationExceptionEvent;
use ApiPlatform\Core\Event\WriteEvent;
use ApiPlatform\Core\Events;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @internal
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class EventDispatcher implements EventSubscriberInterface
{
    private const INTERNAL_EVENTS_CONFIGURATION = [
        KernelEvents::REQUEST => [
            QueryParameterValidateEvent::class => Events::QUERY_PARAMETER_VALIDATE,
            FormatAddEvent::class => Events::FORMAT_ADD,

            PreReadEvent::class => Events::PRE_READ,
            ReadEvent::class => Events::READ,
            PostReadEvent::class => Events::POST_READ,

            PreDeserializeEvent::class => Events::PRE_DESERIALIZE,
            DeserializeEvent::class => Events::DESERIALIZE,
            PostDeserializeEvent::class => Events::POST_DESERIALIZE,
        ],
        KernelEvents::VIEW => [
            PreValidateEvent::class => Events::PRE_VALIDATE,
            ValidateEvent::class => Events::VALIDATE,
            PostValidateEvent::class => Events::POST_VALIDATE,

            PreWriteEvent::class => Events::PRE_WRITE,
            WriteEvent::class => Events::WRITE,
            PostWriteEvent::class => Events::POST_WRITE,

            PreSerializeEvent::class => Events::PRE_SERIALIZE,
            SerializeEvent::class => Events::SERIALIZE,
            PostSerializeEvent::class => Events::POST_SERIALIZE,

            PreRespondEvent::class => Events::PRE_RESPOND,
            RespondEvent::class => Events::RESPOND,
            PostRespondEvent::class => Events::POST_RESPOND,
        ],
        KernelEvents::EXCEPTION => [
            PreValidationExceptionEvent::class => Events::PRE_VALIDATE_EXCEPTION,
            ValidationExceptionEvent::class => Events::VALIDATE_EXCEPTION,
            PostValidationExceptionEvent::class => Events::POST_VALIDATE_EXCEPTION,
        ]
    ];

    private $dispatcher;

    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'dispatch',
            KernelEvents::VIEW => 'dispatch'
        ];
    }

    public function dispatch(Event $event, string $eventName): void
    {
        $internalEventData = null;
        $internalEventContext = [];

        // case order is important because of inheritance
        switch (true) {
            case $event instanceof GetResponseForControllerResultEvent:
                $internalEventData = $event->getControllerResult();
                $internalEventContext = ['request' => $event->getRequest()];
                break;
            case $event instanceof GetResponseForExceptionEvent:
                $internalEventContext = ['request' => $event->getRequest(), 'exception' => $event->getException()];
                break;
            case $event instanceof GetResponseEvent:
                $internalEventContext = ['request' => $event->getRequest()];
                break;
        }

        foreach (self::INTERNAL_EVENTS_CONFIGURATION[$eventName] as $internalEventClass => $internalEventName) {
            $internalEvent = new $internalEventClass($internalEventData, $internalEventContext);

            $this->dispatcher->dispatch($internalEventName, $internalEvent);
        }
    }
}
