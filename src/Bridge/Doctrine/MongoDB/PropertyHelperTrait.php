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

namespace ApiPlatform\Core\Bridge\Doctrine\MongoDB;

use ApiPlatform\Core\Exception\InvalidArgumentException;
use Doctrine\ODM\MongoDB\Aggregation\Builder;

/**
 * Helper trait regarding a property in a MongoDB document using the resource metadata.
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
trait PropertyHelperTrait
{
    /**
     * Adds the necessary lookups for a nested property.
     *
     * @throws InvalidArgumentException If property is not nested
     *
     * @return array An array where the first element is the $alias of the lookup,
     *               the second element is the $field name
     *               the third element is the $associations array
     */
    protected function addLookupsForNestedProperty(string $property, Builder $aggregationBuilder, string $resourceClass): array
    {
        $propertyParts = $this->splitPropertyParts($property, $resourceClass);
        $association = $propertyParts['associations'][0] ?? null;

        if (null === $association) {
            throw new InvalidArgumentException(sprintf('Cannot add lookups for property "%s" - property is not nested.', $property));
        }

        $alias = $association;
        if ($this->getClassMetadata($resourceClass)->hasReference($association)) {
            $alias = "${association}_lkup";
            $aggregationBuilder->lookup($association)->alias($alias);
        }

        // assocation.property => association_lkup.property
        return [str_replace($association, $alias, $property), $propertyParts['field'], $propertyParts['associations']];
    }
}
