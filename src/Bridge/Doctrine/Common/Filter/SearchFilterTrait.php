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

namespace ApiPlatform\Core\Bridge\Doctrine\Common\Filter;

use ApiPlatform\Core\Exception\InvalidArgumentException;
use Doctrine\DBAL\Types\Type;

/**
 * Trait for filtering the collection by given properties.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
trait SearchFilterTrait
{
    protected $iriConverter;
    protected $propertyAccessor;

    /**
     * {@inheritdoc}
     */
    public function getDescription(string $resourceClass): array
    {
        $description = [];

        $properties = $this->properties;
        if (null === $properties) {
            $properties = array_fill_keys($this->getClassMetadata($resourceClass)->getFieldNames(), null);
        }

        foreach ($properties as $property => $strategy) {
            if (!$this->isPropertyMapped($property, $resourceClass, true)) {
                continue;
            }

            if ($this->isPropertyNested($property, $resourceClass)) {
                $propertyParts = $this->splitPropertyParts($property, $resourceClass);
                $field = $propertyParts['field'];
                $metadata = $this->getNestedMetadata($resourceClass, $propertyParts['associations']);
            } else {
                $field = $property;
                $metadata = $this->getClassMetadata($resourceClass);
            }

            if ($metadata->hasField($field)) {
                $typeOfField = $this->getType($metadata->getTypeOfField($field));
                $strategy = $this->properties[$property] ?? self::STRATEGY_EXACT;
                $filterParameterNames = [$property];

                if (self::STRATEGY_EXACT === $strategy) {
                    $filterParameterNames[] = $property.'[]';
                }

                foreach ($filterParameterNames as $filterParameterName) {
                    $description[$filterParameterName] = [
                        'property' => $property,
                        'type' => $typeOfField,
                        'required' => false,
                        'strategy' => $strategy,
                    ];
                }
            } elseif ($metadata->hasAssociation($field)) {
                $filterParameterNames = [
                    $property,
                    $property.'[]',
                ];

                foreach ($filterParameterNames as $filterParameterName) {
                    $description[$filterParameterName] = [
                        'property' => $property,
                        'type' => 'string',
                        'required' => false,
                        'strategy' => self::STRATEGY_EXACT,
                    ];
                }
            }
        }

        return $description;
    }

    /**
     * Converts a Doctrine type in PHP type.
     */
    private function getType(string $doctrineType): string
    {
        switch ($doctrineType) {
            case Type::TARRAY:
                return 'array';
            case Type::BIGINT:
            case Type::INTEGER:
            case Type::SMALLINT:
                return 'int';
            case Type::BOOLEAN:
                return 'bool';
            case Type::DATE:
            case Type::TIME:
            case Type::DATETIME:
            case Type::DATETIMETZ:
                return \DateTimeInterface::class;
            case Type::FLOAT:
                return 'float';
        }

        if (\defined(Type::class.'::DATE_IMMUTABLE')) {
            switch ($doctrineType) {
                case Type::DATE_IMMUTABLE:
                case Type::TIME_IMMUTABLE:
                case Type::DATETIME_IMMUTABLE:
                case Type::DATETIMETZ_IMMUTABLE:
                    return \DateTimeInterface::class;
            }
        }

        return 'string';
    }

    /**
     * Gets the ID from an IRI or a raw ID.
     */
    private function getIdFromValue(string $value)
    {
        try {
            if ($item = $this->iriConverter->getItemFromIri($value, ['fetch_data' => false])) {
                return $this->propertyAccessor->getValue($item, 'id');
            }
        } catch (InvalidArgumentException $e) {
            // Do nothing, return the raw value
        }

        return $value;
    }

    /**
     * Normalize the values array.
     */
    private function normalizeValues(array $values): array
    {
        foreach ($values as $key => $value) {
            if (!\is_int($key) || !\is_string($value)) {
                unset($values[$key]);
            }
        }

        return array_values($values);
    }

    /**
     * When the field should be an integer, check that the given value is a valid one.
     *
     * @param Type|string $type
     */
    private function hasValidValues(array $values, $type = null): bool
    {
        foreach ($values as $key => $value) {
            if (Type::INTEGER === $type && null !== $value && false === filter_var($value, FILTER_VALIDATE_INT)) {
                return false;
            }
        }

        return true;
    }
}