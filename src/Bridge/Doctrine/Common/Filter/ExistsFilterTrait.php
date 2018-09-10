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

/**
 * Trait for filtering the collection by whether a property value exists or not.
 *
 * @author Teoh Han Hui <teohhanhui@gmail.com>
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
trait ExistsFilterTrait
{
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

        foreach ($properties as $property => $unused) {
            if (!$this->isPropertyMapped($property, $resourceClass, true) || !$this->isNullableField($property, $resourceClass)) {
                continue;
            }

            $description[sprintf('%s[%s]', $property, self::QUERY_PARAMETER_KEY)] = [
                'property' => $property,
                'type' => 'bool',
                'required' => false,
            ];
        }

        return $description;
    }

    private function normalizeValue($value, string $property): ?bool
    {
        if (\in_array($value[self::QUERY_PARAMETER_KEY], [true, 'true', '1', '', null], true)) {
            $value = true;
        } elseif (\in_array($value[self::QUERY_PARAMETER_KEY], [false, 'false', '0'], true)) {
            $value = false;
        } else {
            $this->logger->notice('Invalid filter ignored', [
                'exception' => new InvalidArgumentException(sprintf('Invalid value for "%s[%s]", expected one of ( "%s" )', $property, self::QUERY_PARAMETER_KEY, implode('" | "', [
                    'true',
                    'false',
                    '1',
                    '0',
                ]))),
            ]);

            $value = null;
        }

        return $value;
    }
}
