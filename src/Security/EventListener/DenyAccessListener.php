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

namespace ApiPlatform\Core\Security\EventListener;

use ApiPlatform\Core\Event\EventInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Security\ExpressionLanguage;
use ApiPlatform\Core\Security\ResourceAccessChecker;
use ApiPlatform\Core\Security\ResourceAccessCheckerInterface;
use ApiPlatform\Core\Util\RequestAttributesExtractor;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolverInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

/**
 * Denies access to the current resource if the logged user doesn't have sufficient permissions.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class DenyAccessListener
{
    private $resourceMetadataFactory;
    private $resourceAccessChecker;

    public function __construct(ResourceMetadataFactoryInterface $resourceMetadataFactory, /*ResourceAccessCheckerInterface*/ $resourceAccessCheckerOrExpressionLanguage = null, AuthenticationTrustResolverInterface $authenticationTrustResolver = null, RoleHierarchyInterface $roleHierarchy = null, TokenStorageInterface $tokenStorage = null, AuthorizationCheckerInterface $authorizationChecker = null)
    {
        $this->resourceMetadataFactory = $resourceMetadataFactory;

        if ($resourceAccessCheckerOrExpressionLanguage instanceof ResourceAccessCheckerInterface) {
            $this->resourceAccessChecker = $resourceAccessCheckerOrExpressionLanguage;

            return;
        }

        $this->resourceAccessChecker = new ResourceAccessChecker($resourceAccessCheckerOrExpressionLanguage, $authenticationTrustResolver, $roleHierarchy, $tokenStorage, $authorizationChecker);
        @trigger_error(sprintf('Passing an instance of "%s" or null as second argument of "%s" is deprecated since API Platform 2.2 and will not be possible anymore in API Platform 3. Pass an instance of "%s" and no extra argument instead.', ExpressionLanguage::class, self::class, ResourceAccessCheckerInterface::class), E_USER_DEPRECATED);
    }

    /**
     * Sets the applicable format to the HttpFoundation Request.
     *
     * @deprecated since version 2.5, to be removed in 3.0.
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        @trigger_error(sprintf('The method %s() is deprecated since 2.5 and will be removed in 3.0.', __METHOD__), E_USER_DEPRECATED);

        $this->handleEvent($event);
    }

    /**
     * Sets the applicable format to the HttpFoundation Request.
     *
     * @throws AccessDeniedException
     */
    public function handleEvent(/*EventInterface */$event)
    {
        if ($event instanceof EventInterface) {
            $request = $event->getContext()['request'];
        } elseif ($event instanceof GetResponseEvent) {
            @trigger_error(sprintf('Passing an instance of "%s" as argument of "%s" is deprecated since 2.5 and will not be possible anymore in 3.0. Pass an instance of "%s" instead.', GetResponseEvent::class, __METHOD__, EventInterface::class), E_USER_DEPRECATED);

            $request = $event->getRequest();
        } else {
            return;
        }
        if (!$attributes = RequestAttributesExtractor::extractAttributes($request)) {
            return;
        }

        $resourceMetadata = $this->resourceMetadataFactory->create($attributes['resource_class']);

        $isGranted = $resourceMetadata->getOperationAttribute($attributes, 'access_control', null, true);

        if (null === $isGranted) {
            return;
        }

        $extraVariables = $request->attributes->all();
        $extraVariables['object'] = $request->attributes->get('data');
        $extraVariables['request'] = $request;

        if (!$this->resourceAccessChecker->isGranted($attributes['resource_class'], $isGranted, $extraVariables)) {
            throw new AccessDeniedException($resourceMetadata->getOperationAttribute($attributes, 'access_control_message', 'Access Denied.', true));
        }
    }
}
