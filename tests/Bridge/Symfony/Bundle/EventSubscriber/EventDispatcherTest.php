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

namespace ApiPlatform\Core\Tests\Bridge\Symfony\Bundle\EventSubscriber;

use ApiPlatform\Core\Bridge\Symfony\Bundle\EventSubscriber\EventDispatcher;
use ApiPlatform\Core\Event\DeserializeEvent;
use ApiPlatform\Core\Event\FormatAddEvent;
use ApiPlatform\Core\Event\PostDeserializeEvent;
use ApiPlatform\Core\Event\PostReadEvent;
use ApiPlatform\Core\Event\PostRespondEvent;
use ApiPlatform\Core\Event\PostSerializeEvent;
use ApiPlatform\Core\Event\PostValidateEvent;
use ApiPlatform\Core\Event\PostWriteEvent;
use ApiPlatform\Core\Event\PreDeserializeEvent;
use ApiPlatform\Core\Event\PreReadEvent;
use ApiPlatform\Core\Event\PreRespondEvent;
use ApiPlatform\Core\Event\PreSerializeEvent;
use ApiPlatform\Core\Event\PreValidateEvent;
use ApiPlatform\Core\Event\PreWriteEvent;
use ApiPlatform\Core\Event\QueryParameterValidateEvent;
use ApiPlatform\Core\Event\ReadEvent;
use ApiPlatform\Core\Event\RespondEvent;
use ApiPlatform\Core\Event\SerializeEvent;
use ApiPlatform\Core\Event\ValidateEvent;
use ApiPlatform\Core\Event\WriteEvent;
use ApiPlatform\Core\Events;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class EventDispatcherTest extends TestCase
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
        ]
    ];

    public function testGetSubscribedEvents(): void
    {
        $this->assertEquals(
            ['kernel.request' => 'dispatch', 'kernel.view' => 'dispatch'],
            EventDispatcher::getSubscribedEvents()
        );
    }

    public function testDispatchWithRequestKernelEvent()
    {
        $kernel = $this->prophesize(HttpKernelInterface::class);
        $request = $this->prophesize(Request::class);

        $event = new GetResponseEvent($kernel->reveal(), $request->reveal(), HttpKernelInterface::MASTER_REQUEST);

        /** @var EventDispatcherInterface $symfonyEventDispatcher */
        $symfonyEventDispatcher = $this->prophesize(EventDispatcherInterface::class);

        foreach (self::INTERNAL_EVENTS_CONFIGURATION['kernel.request'] as $internalEventClass => $internalEventName) {
            $internalEvent = new $internalEventClass(null, ['request' => $event->getRequest()]);
            $symfonyEventDispatcher->dispatch($internalEventName, $internalEvent)->shouldBeCalledOnce();
        }

        $eventDispatcher = new EventDispatcher($symfonyEventDispatcher->reveal());
        $eventDispatcher->dispatch($event, 'kernel.request');
    }

    public function testDispatchWithViewKernelEvent()
    {
        $kernel = $this->prophesize(HttpKernelInterface::class);
        $request = $this->prophesize(Request::class);

        $event = new GetResponseForControllerResultEvent($kernel->reveal(), $request->reveal(), HttpKernelInterface::MASTER_REQUEST, ['data' => 'test']);

        /** @var EventDispatcherInterface $symfonyEventDispatcher */
        $symfonyEventDispatcher = $this->prophesize(EventDispatcherInterface::class);

        foreach (self::INTERNAL_EVENTS_CONFIGURATION['kernel.view'] as $internalEventClass => $internalEventName) {
            $internalEvent = new $internalEventClass(['data' => 'test'], ['request' => $event->getRequest()]);
            $symfonyEventDispatcher->dispatch($internalEventName, $internalEvent)->shouldBeCalledOnce();
        }

        $eventDispatcher = new EventDispatcher($symfonyEventDispatcher->reveal());
        $eventDispatcher->dispatch($event, 'kernel.view');
    }
}
