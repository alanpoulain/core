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
use ApiPlatform\Core\Event\PreDeserializeEvent;
use ApiPlatform\Core\Event\PreReadEvent;
use ApiPlatform\Core\Event\QueryParameterValidateEvent;
use ApiPlatform\Core\Event\ReadEvent;
use ApiPlatform\Core\Events;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
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
        ];
    }

    public function dispatch(Event $event, string $eventName): void
    {
        $internalEventData = null;
        $internalEventContext = [];

        switch (true) {
            case $event instanceof GetResponseEvent:
                $internalEventContext = ['request' => $event->getRequest()];
        }

        foreach ($this->getInternalEventData($eventName) as $internalEventClass => $internalEventName) {
            $internalEvent = new $internalEventClass($internalEventData, $internalEventContext);

            $this->dispatcher->dispatch($internalEventName, $internalEvent);
        }
    }

    private function getInternalEventData(string $eventName): \Generator
    {
        yield from self::INTERNAL_EVENTS_CONFIGURATION[$eventName];
    }
}
