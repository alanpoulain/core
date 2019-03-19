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

namespace ApiPlatform\Core\Tests\GraphQl\Type;

use ApiPlatform\Core\Exception\ResourceClassNotFoundException;
use ApiPlatform\Core\GraphQl\Type\TypeBuilderInterface;
use ApiPlatform\Core\GraphQl\Type\TypeConverter;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type as GraphQLType;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\PropertyInfo\Type;

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class TypeConverterTest extends TestCase
{
    /** @var ObjectProphecy */
    private $typeBuilderProphecy;

    /** @var ObjectProphecy */
    private $resourceMetadataFactoryProphecy;

    /** @var TypeConverter */
    private $typeConverter;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->typeBuilderProphecy = $this->prophesize(TypeBuilderInterface::class);
        $this->resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $this->typeConverter = new TypeConverter($this->typeBuilderProphecy->reveal(), $this->resourceMetadataFactoryProphecy->reveal());
    }

    /**
     * @dataProvider convertTypeProvider
     *
     * @param string|GraphQLType|null $expectedGraphqlType
     */
    public function testConvertType(Type $type, bool $input, int $depth, $expectedGraphqlType): void
    {
        $this->typeBuilderProphecy->isCollection($type)->willReturn(false);

        $graphqlType = $this->typeConverter->convertType($type, $input, null, null, 'resourceClass', null, $depth);
        $this->assertEquals($expectedGraphqlType, $graphqlType);
    }

    public function convertTypeProvider(): array
    {
        return [
            [new Type(Type::BUILTIN_TYPE_BOOL), false, 0, GraphQLType::boolean()],
            [new Type(Type::BUILTIN_TYPE_INT), false, 0, GraphQLType::int()],
            [new Type(Type::BUILTIN_TYPE_FLOAT), false, 0, GraphQLType::float()],
            [new Type(Type::BUILTIN_TYPE_STRING), false, 0, GraphQLType::string()],
            [new Type(Type::BUILTIN_TYPE_ARRAY), false, 0, 'Iterable'],
            [new Type(Type::BUILTIN_TYPE_ITERABLE), false, 0, 'Iterable'],
            [new Type(Type::BUILTIN_TYPE_OBJECT), true, 1, GraphQLType::string()],
            [new Type(Type::BUILTIN_TYPE_OBJECT, false, \DateTimeInterface::class), false, 0, GraphQLType::string()],
            [new Type(Type::BUILTIN_TYPE_OBJECT), false, 0, null],
            [new Type(Type::BUILTIN_TYPE_CALLABLE), false, 0, null],
            [new Type(Type::BUILTIN_TYPE_NULL), false, 0, null],
            [new Type(Type::BUILTIN_TYPE_RESOURCE), false, 0, null],
        ];
    }

    public function testConvertTypeNoGraphQlResourceMetadata(): void
    {
        $type = new Type(Type::BUILTIN_TYPE_OBJECT, false, 'dummy');

        $this->typeBuilderProphecy->isCollection($type)->shouldBeCalled()->willReturn(false);
        $this->resourceMetadataFactoryProphecy->create('dummy')->shouldBeCalled()->willReturn(new ResourceMetadata());

        $graphqlType = $this->typeConverter->convertType($type, false, null, null, 'resourceClass', null, 0);
        $this->assertNull($graphqlType);
    }

    public function testConvertTypeResourceClassNotFound(): void
    {
        $type = new Type(Type::BUILTIN_TYPE_OBJECT, false, 'dummy');

        $this->typeBuilderProphecy->isCollection($type)->shouldBeCalled()->willReturn(false);
        $this->resourceMetadataFactoryProphecy->create('dummy')->shouldBeCalled()->willThrow(new ResourceClassNotFoundException());

        $graphqlType = $this->typeConverter->convertType($type, false, null, null, 'resourceClass', null, 0);
        $this->assertNull($graphqlType);
    }

    public function testConvertTypeResource(): void
    {
        $graphqlResourceMetadata = (new ResourceMetadata())->withGraphql(['test']);
        $type = new Type(Type::BUILTIN_TYPE_OBJECT, false, null, true, null, new Type(Type::BUILTIN_TYPE_OBJECT, false, 'dummyValue'));
        $expectedGraphqlType = new ObjectType(['name' => 'resourceObjectType']);

        $this->typeBuilderProphecy->isCollection($type)->shouldBeCalled()->willReturn(true);
        $this->resourceMetadataFactoryProphecy->create('dummyValue')->shouldBeCalled()->willReturn($graphqlResourceMetadata);
        $this->typeBuilderProphecy->getResourceObjectType('dummyValue', $graphqlResourceMetadata, false, null, null, false, 0)->shouldBeCalled()->willReturn($expectedGraphqlType);

        $graphqlType = $this->typeConverter->convertType($type, false, null, null, 'resourceClass', null, 0);
        $this->assertEquals($expectedGraphqlType, $graphqlType);
    }
}
