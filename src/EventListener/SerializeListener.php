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

namespace ApiPlatform\Core\EventListener;

use ApiPlatform\Core\Event\EventInterface;
use ApiPlatform\Core\Exception\RuntimeException;
use ApiPlatform\Core\Serializer\ResourceList;
use ApiPlatform\Core\Serializer\SerializerContextBuilderInterface;
use ApiPlatform\Core\Util\RequestAttributesExtractor;
use Fig\Link\GenericLinkProvider;
use Fig\Link\Link;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\Serializer\Encoder\EncoderInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Serializes data.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class SerializeListener
{
    private $serializer;
    private $serializerContextBuilder;

    public function __construct(SerializerInterface $serializer, SerializerContextBuilderInterface $serializerContextBuilder)
    {
        $this->serializer = $serializer;
        $this->serializerContextBuilder = $serializerContextBuilder;
    }

    /**
     * Serializes the data to the requested format.
     *
     * @deprecated The method ApiPlatform\Core\EventListener\SerializeListener::onKernelView() is deprecated since 2.5 and will be removed in 3.0.
     */
    public function onKernelView(GetResponseForControllerResultEvent $event)
    {
        @trigger_error(sprintf('The method %s() is deprecated since 2.5 and will be removed in 3.0.', __METHOD__), E_USER_DEPRECATED);

        $this->handleEvent($event);
    }

    /**
     * Serializes the data to the requested format.
     */
    public function handleEvent(/* EventInterface */ $event)
    {
        if ($event instanceof EventInterface) {
            $controllerResult = $event->getData();
            $request = $event->getContext()['request'];
        } elseif ($event instanceof GetResponseForControllerResultEvent) {
            @trigger_error(sprintf('Passing an instance of "%s" as argument of "ApiPlatform\Core\EventListener\SerializeListener::handleEvent" is deprecated since 2.5 and will not be possible anymore in 3.0. Pass an instance of "%s" instead.', GetResponseForControllerResultEvent::class, EventInterface::class), E_USER_DEPRECATED);
            $controllerResult = $event->getControllerResult();
            $request = $event->getRequest();
        } else {
            return;
        }

        if ($controllerResult instanceof Response || !$request->attributes->getBoolean('_api_respond', true)) {
            return;
        }

        if (!$attributes = RequestAttributesExtractor::extractAttributes($request)) {
            $this->serializeRawData($event, $request, $controllerResult);

            return;
        }

        $request->attributes->set('_api_respond', true);
        $context = $this->serializerContextBuilder->createFromRequest($request, true, $attributes);

        if (isset($context['output']) && \array_key_exists('class', $context['output']) && null === $context['output']['class']) {

            if ($event instanceof EventInterface) {
                $event->setData('');
            } elseif ($event instanceof GetResponseForControllerResultEvent) {
                $event->setControllerResult('');
            }

            return;
        }

        if ($included = $request->attributes->get('_api_included')) {
            $context['api_included'] = $included;
        }
        $resources = new ResourceList();
        $context['resources'] = &$resources;

        $resourcesToPush = new ResourceList();
        $context['resources_to_push'] = &$resourcesToPush;

        $request->attributes->set('_api_normalization_context', $context);

        if ($event instanceof EventInterface) {
            $event->setData($this->serializer->serialize($controllerResult, $request->getRequestFormat(), $context));
        } elseif ($event instanceof GetResponseForControllerResultEvent) {
            $event->setControllerResult($this->serializer->serialize($controllerResult, $request->getRequestFormat(), $context));
        }

        $request->attributes->set('_resources', $request->attributes->get('_resources', []) + (array) $resources);
        if (!\count($resourcesToPush)) {
            return;
        }

        $linkProvider = $request->attributes->get('_links', new GenericLinkProvider());
        foreach ($resourcesToPush as $resourceToPush) {
            $linkProvider = $linkProvider->withLink(new Link('preload', $resourceToPush));
        }
        $request->attributes->set('_links', $linkProvider);
    }

    /**
     * Tries to serialize data that are not API resources (e.g. the entrypoint or data returned by a custom controller).
     *
     * @param object $controllerResult
     *
     * @throws RuntimeException
     */
    private function serializeRawData(/* EventInterface */ $event, Request $request, $controllerResult)
    {
        if (\is_object($controllerResult)) {
            if ($event instanceof EventInterface) {
                $event->setData($this->serializer->serialize($controllerResult, $request->getRequestFormat(), $request->attributes->get('_api_normalization_context', [])));
            } elseif ($event instanceof GetResponseForControllerResultEvent) {
                $event->setControllerResult($this->serializer->serialize($controllerResult, $request->getRequestFormat(), $request->attributes->get('_api_normalization_context', [])));
            }

            return;
        }

        if (!$this->serializer instanceof EncoderInterface) {
            throw new RuntimeException(sprintf('The serializer instance must implements the "%s" interface.', EncoderInterface::class));
        }

        if ($event instanceof EventInterface) {
            $event->setData($this->serializer->encode($controllerResult, $request->getRequestFormat()));
        } elseif ($event instanceof GetResponseForControllerResultEvent) {
            $event->setControllerResult($this->serializer->encode($controllerResult, $request->getRequestFormat()));
        }
    }
}
