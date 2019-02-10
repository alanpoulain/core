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

namespace ApiPlatform\Core\Tests\EventListener;

use ApiPlatform\Core\Api\FormatsProviderInterface;
use ApiPlatform\Core\Event\EventInterface;
use ApiPlatform\Core\EventListener\DeserializeListener;
use ApiPlatform\Core\Serializer\SerializerContextBuilderInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class DeserializeListenerTest extends TestCase
{
    public const FORMATS = ['json' => ['application/json']];

    public function testDoNotCallWhenRequestMethodIsSafe()
    {
        $request = new Request([], [], ['data' => new \stdClass()]);
        $request->setMethod('GET');
        $eventProphecy = $this->prophesize(EventInterface::class);
        $eventProphecy->getContext()->willReturn(['request' => $request]);

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->deserialize()->shouldNotBeCalled();

        $serializerContextBuilderProphecy = $this->prophesize(SerializerContextBuilderInterface::class);
        $serializerContextBuilderProphecy->createFromRequest(Argument::type(Request::class), false, Argument::type('array'))->shouldNotBeCalled();

        $formatsProviderProphecy = $this->prophesize(FormatsProviderInterface::class);
        $formatsProviderProphecy->getFormatsFromAttributes(Argument::type('array'))->shouldNotBeCalled();

        $listener = new DeserializeListener($serializerProphecy->reveal(), $serializerContextBuilderProphecy->reveal(), $formatsProviderProphecy->reveal());
        $listener->handleEvent($eventProphecy->reveal());
    }

    /**
     * @dataProvider allowedEmptyRequestMethodsProvider
     */
    public function testDoNotCallWhenSendingAndEmptyRequestContent($method)
    {
        $request = new Request([], [], ['data' => new \stdClass(), '_api_resource_class' => 'Foo', '_api_item_operation_name' => 'put'], [], [], [], '');
        $request->setMethod($method);
        $request->headers->set('Content-Type', 'application/json');
        $eventProphecy = $this->prophesize(EventInterface::class);
        $eventProphecy->getContext()->willReturn(['request' => $request]);

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->deserialize()->shouldNotBeCalled();

        $serializerContextBuilderProphecy = $this->prophesize(SerializerContextBuilderInterface::class);
        $serializerContextBuilderProphecy->createFromRequest(Argument::type(Request::class), false, Argument::type('array'))->shouldNotBeCalled();

        $formatsProviderProphecy = $this->prophesize(FormatsProviderInterface::class);
        $formatsProviderProphecy->getFormatsFromAttributes(Argument::type('array'))->shouldNotBeCalled();

        $listener = new DeserializeListener($serializerProphecy->reveal(), $serializerContextBuilderProphecy->reveal(), $formatsProviderProphecy->reveal());
        $listener->handleEvent($eventProphecy->reveal());
    }

    public function allowedEmptyRequestMethodsProvider()
    {
        return [['PUT'], ['POST']];
    }

    public function testDoNotCallWhenRequestNotManaged()
    {
        $request = new Request([], [], ['data' => new \stdClass()], [], [], [], '{}');
        $request->setMethod('POST');
        $eventProphecy = $this->prophesize(EventInterface::class);
        $eventProphecy->getContext()->willReturn(['request' => $request]);

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->deserialize()->shouldNotBeCalled();

        $serializerContextBuilderProphecy = $this->prophesize(SerializerContextBuilderInterface::class);
        $serializerContextBuilderProphecy->createFromRequest(Argument::type(Request::class), false, Argument::type('array'))->shouldNotBeCalled();

        $formatsProviderProphecy = $this->prophesize(FormatsProviderInterface::class);
        $formatsProviderProphecy->getFormatsFromAttributes(Argument::type('array'))->shouldNotBeCalled();

        $listener = new DeserializeListener($serializerProphecy->reveal(), $serializerContextBuilderProphecy->reveal(), $formatsProviderProphecy->reveal());
        $listener->handleEvent($eventProphecy->reveal());
    }

    public function testDoNotCallWhenReceiveFlagIsFalse()
    {
        $request = new Request([], [], ['data' => new \stdClass(), '_api_resource_class' => 'Foo', '_api_collection_operation_name' => 'post', '_api_receive' => false]);
        $request->setMethod('POST');
        $eventProphecy = $this->prophesize(EventInterface::class);
        $eventProphecy->getContext()->willReturn(['request' => $request]);

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->deserialize()->shouldNotBeCalled();

        $serializerContextBuilderProphecy = $this->prophesize(SerializerContextBuilderInterface::class);
        $serializerContextBuilderProphecy->createFromRequest(Argument::type(Request::class), false, Argument::type('array'))->shouldNotBeCalled();

        $formatsProviderProphecy = $this->prophesize(FormatsProviderInterface::class);
        $formatsProviderProphecy->getFormatsFromAttributes(Argument::type('array'))->shouldNotBeCalled();

        $listener = new DeserializeListener($serializerProphecy->reveal(), $serializerContextBuilderProphecy->reveal(), $formatsProviderProphecy->reveal());
        $listener->handleEvent($eventProphecy->reveal());
    }

    public function testDoNotCallWhenInputClassDisabled()
    {
        $request = new Request([], [], ['data' => new \stdClass(), '_api_resource_class' => 'Foo', '_api_collection_operation_name' => 'post'], [], [], [], 'content');
        $request->setMethod('POST');
        $eventProphecy = $this->prophesize(EventInterface::class);
        $eventProphecy->getContext()->willReturn(['request' => $request]);

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->deserialize()->shouldNotBeCalled();

        $serializerContextBuilderProphecy = $this->prophesize(SerializerContextBuilderInterface::class);
        $serializerContextBuilderProphecy->createFromRequest(Argument::type(Request::class), false, Argument::type('array'))->willReturn(['input' => ['class' => null], 'output' => ['class' => null]]);

        $formatsProviderProphecy = $this->prophesize(FormatsProviderInterface::class);
        $formatsProviderProphecy->getFormatsFromAttributes(Argument::type('array'))->shouldNotBeCalled();

        $listener = new DeserializeListener($serializerProphecy->reveal(), $serializerContextBuilderProphecy->reveal(), $formatsProviderProphecy->reveal());
        $listener->handleEvent($eventProphecy->reveal());
    }

    /**
     * @dataProvider methodProvider
     */
    public function testDeserialize(string $method, bool $populateObject)
    {
        $result = $populateObject ? new \stdClass() : null;

        $request = new Request([], [], ['data' => $result, '_api_resource_class' => 'Foo', '_api_collection_operation_name' => 'post'], [], [], [], '{}');
        $request->setMethod($method);
        $request->headers->set('Content-Type', 'application/json');
        $eventProphecy = $this->prophesize(EventInterface::class);
        $eventProphecy->getContext()->willReturn(['request' => $request]);

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $context = $populateObject ? [AbstractNormalizer::OBJECT_TO_POPULATE => $populateObject] : [];
        $context['input'] = ['class' => 'Foo'];
        $context['output'] = ['class' => 'Foo'];
        $context['resource_class'] = 'Foo';
        $serializerProphecy->deserialize('{}', 'Foo', 'json', $context)->willReturn($result)->shouldBeCalled();

        $formatsProviderProphecy = $this->prophesize(FormatsProviderInterface::class);
        $formatsProviderProphecy->getFormatsFromAttributes(Argument::type('array'))->willReturn(self::FORMATS)->shouldBeCalled();

        $serializerContextBuilderProphecy = $this->prophesize(SerializerContextBuilderInterface::class);
        $serializerContextBuilderProphecy->createFromRequest(Argument::type(Request::class), false, Argument::type('array'))->willReturn(['input' => ['class' => 'Foo'], 'output' => ['class' => 'Foo'], 'resource_class' => 'Foo'])->shouldBeCalled();

        $listener = new DeserializeListener($serializerProphecy->reveal(), $serializerContextBuilderProphecy->reveal(), $formatsProviderProphecy->reveal());
        $listener->handleEvent($eventProphecy->reveal());
    }

    /**
     * @dataProvider methodProvider
     */
    public function testDeserializeResourceClassSupportedFormat(string $method, bool $populateObject)
    {
        $result = $populateObject ? new \stdClass() : null;

        $request = new Request([], [], ['data' => $result, '_api_resource_class' => 'Foo', '_api_collection_operation_name' => 'post'], [], [], [], '{}');
        $request->setMethod($method);
        $request->headers->set('Content-Type', 'application/json');
        $eventProphecy = $this->prophesize(EventInterface::class);
        $eventProphecy->getContext()->willReturn(['request' => $request]);

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $context = $populateObject ? [AbstractNormalizer::OBJECT_TO_POPULATE => $populateObject] : [];
        $context['input'] = ['class' => 'Foo'];
        $context['output'] = ['class' => 'Foo'];
        $context['resource_class'] = 'Foo';
        $serializerProphecy->deserialize('{}', 'Foo', 'json', $context)->willReturn($result)->shouldBeCalled();

        $serializerContextBuilderProphecy = $this->prophesize(SerializerContextBuilderInterface::class);
        $serializerContextBuilderProphecy->createFromRequest(Argument::type(Request::class), false, Argument::type('array'))->willReturn(['input' => ['class' => 'Foo'], 'output' => ['class' => 'Foo'], 'resource_class' => 'Foo'])->shouldBeCalled();

        $formatsProviderProphecy = $this->prophesize(FormatsProviderInterface::class);
        $formatsProviderProphecy->getFormatsFromAttributes([
            'resource_class' => 'Foo',
            'collection_operation_name' => 'post',
            'receive' => true,
            'respond' => true,
            'persist' => true,
        ])->willReturn(self::FORMATS)->shouldBeCalled();

        $listener = new DeserializeListener($serializerProphecy->reveal(), $serializerContextBuilderProphecy->reveal(), $formatsProviderProphecy->reveal());

        $listener->handleEvent($eventProphecy->reveal());
    }

    public function methodProvider()
    {
        return [['POST', false], ['PUT', true]];
    }

    public function testContentNegotiation()
    {
        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_collection_operation_name' => 'post'], [], [], [], '{}');
        $request->setMethod('POST');
        $request->headers->set('Content-Type', 'text/xml');
        $request->setFormat('xml', 'text/xml'); // Workaround to avoid weird behaviors
        $eventProphecy = $this->prophesize(EventInterface::class);
        $eventProphecy->getContext()->willReturn(['request' => $request]);

        $context = ['input' => ['class' => 'Foo'], 'output' => ['class' => 'Foo'], 'resource_class' => 'Foo'];

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->deserialize('{}', 'Foo', 'xml', $context)->willReturn(new \stdClass())->shouldBeCalled();

        $serializerContextBuilderProphecy = $this->prophesize(SerializerContextBuilderInterface::class);
        $serializerContextBuilderProphecy->createFromRequest(Argument::type(Request::class), false, Argument::type('array'))->willReturn($context)->shouldBeCalled();

        $formatsProviderProphecy = $this->prophesize(FormatsProviderInterface::class);
        $formatsProviderProphecy->getFormatsFromAttributes(Argument::type('array'))->willReturn(['jsonld' => ['application/ld+json'], 'xml' => ['text/xml']])->shouldBeCalled();

        $listener = new DeserializeListener(
            $serializerProphecy->reveal(),
            $serializerContextBuilderProphecy->reveal(),
            $formatsProviderProphecy->reveal()
        );
        $listener->handleEvent($eventProphecy->reveal());
    }

    public function testNotSupportedContentType()
    {
        $this->expectException(NotAcceptableHttpException::class);
        $this->expectExceptionMessage('The content-type "application/rdf+xml" is not supported. Supported MIME types are "application/ld+json", "text/xml".');

        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_collection_operation_name' => 'post'], [], [], [], '{}');
        $request->setMethod('POST');
        $request->headers->set('Content-Type', 'application/rdf+xml');
        $request->setRequestFormat('xml');
        $eventProphecy = $this->prophesize(EventInterface::class);
        $eventProphecy->getContext()->willReturn(['request' => $request]);

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->deserialize()->shouldNotBeCalled();

        $serializerContextBuilderProphecy = $this->prophesize(SerializerContextBuilderInterface::class);
        $serializerContextBuilderProphecy->createFromRequest(Argument::type(Request::class), false, Argument::type('array'))->willReturn(['input' => ['class' => 'Foo'], 'output' => ['class' => 'Foo']]);

        $formatsProviderProphecy = $this->prophesize(FormatsProviderInterface::class);
        $formatsProviderProphecy->getFormatsFromAttributes(Argument::type('array'))->willReturn(['jsonld' => ['application/ld+json'], 'xml' => ['text/xml']])->shouldBeCalled();

        $listener = new DeserializeListener(
            $serializerProphecy->reveal(),
            $serializerContextBuilderProphecy->reveal(),
            $formatsProviderProphecy->reveal()
        );
        $listener->handleEvent($eventProphecy->reveal());
    }

    public function testNoContentType()
    {
        $this->expectException(NotAcceptableHttpException::class);
        $this->expectExceptionMessage('The "Content-Type" header must exist.');

        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_collection_operation_name' => 'post'], [], [], [], '{}');
        $request->setMethod('POST');
        $request->setRequestFormat('unknown');
        $eventProphecy = $this->prophesize(EventInterface::class);
        $eventProphecy->getContext()->willReturn(['request' => $request]);

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->deserialize()->shouldNotBeCalled();

        $serializerContextBuilderProphecy = $this->prophesize(SerializerContextBuilderInterface::class);
        $serializerContextBuilderProphecy->createFromRequest(Argument::type(Request::class), false, Argument::type('array'))->willReturn(['input' => ['class' => 'Foo'], 'output' => ['class' => 'Foo']]);

        $formatsProviderProphecy = $this->prophesize(FormatsProviderInterface::class);
        $formatsProviderProphecy->getFormatsFromAttributes(Argument::type('array'))->willReturn(['jsonld' => ['application/ld+json'], 'xml' => ['text/xml']])->shouldBeCalled();

        $listener = new DeserializeListener(
            $serializerProphecy->reveal(),
            $serializerContextBuilderProphecy->reveal(),
            $formatsProviderProphecy->reveal()
        );
        $listener->handleEvent($eventProphecy->reveal());
    }

    public function testBadFormatsProviderParameterThrowsException()
    {
        $this->expectException(\ApiPlatform\Core\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "$formatsProvider" argument is expected to be an implementation of the "ApiPlatform\\Core\\Api\\FormatsProviderInterface" interface.');

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->deserialize()->shouldNotBeCalled();

        $serializerContextBuilderProphecy = $this->prophesize(SerializerContextBuilderInterface::class);
        $serializerContextBuilderProphecy->createFromRequest()->shouldNotBeCalled();

        new DeserializeListener(
            $serializerProphecy->reveal(),
            $serializerContextBuilderProphecy->reveal(),
            'foo'
        );
    }

    /**
     * @group legacy
     * @expectedDeprecation Using an array as formats provider is deprecated since API Platform 2.3 and will not be possible anymore in API Platform 3
     */
    public function testLegacyFormatsParameter()
    {
        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->deserialize()->shouldNotBeCalled();

        $serializerContextBuilderProphecy = $this->prophesize(SerializerContextBuilderInterface::class);
        $serializerContextBuilderProphecy->createFromRequest()->shouldNotBeCalled();

        new DeserializeListener(
            $serializerProphecy->reveal(),
            $serializerContextBuilderProphecy->reveal(),
            self::FORMATS
        );
    }

    /**
     * @group legacy
     *
     * @expectedDeprecation The method ApiPlatform\Core\EventListener\DeserializeListener::onKernelRequest() is deprecated since 2.5 and will be removed in 3.0.
     * @expectedDeprecation Passing an instance of "Symfony\Component\HttpKernel\Event\GetResponseEvent" as argument of "ApiPlatform\Core\EventListener\DeserializeListener::handleEvent" is deprecated since 2.5 and will not be possible anymore in 3.0. Pass an instance of "ApiPlatform\Core\Event\EventInterface" instead.
     */
    public function testLegacyOnKernelRequest()
    {
        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerContextBuilderProphecy = $this->prophesize(SerializerContextBuilderInterface::class);
        $formatsProviderProphecy = $this->prophesize(FormatsProviderInterface::class);

        $eventProphecy = $this->prophesize(GetResponseEvent::class);
        $eventProphecy->getRequest()->willReturn(new Request());

        $listener = new DeserializeListener(
            $serializerProphecy->reveal(),
            $serializerContextBuilderProphecy->reveal(),
            $formatsProviderProphecy->reveal()
        );
        $listener->onKernelRequest($eventProphecy->reveal());
    }
}
