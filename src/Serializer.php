<?php

namespace JWorman\Serializer;

use JWorman\Serializer\Attribute\SerializedName;
use JWorman\Serializer\Attribute\Type;

class Serializer
{
    final public const FORMAT_JSON = 'format-json';
    final public const ARRAY_TYPE_EXPRESSION_MATCHER = '/^array<(.*)>$/';

    final public static function serialize(mixed $payload, string $format = self::FORMAT_JSON, int $recursionLimit = 512): string
    {
        if ($recursionLimit < 0) {
            throw new \InvalidArgumentException('The recursion limit cannot be negative.');
        }

        return match ($format) {
            self::FORMAT_JSON => JsonSerializer::serializeValue($payload, $recursionLimit),
            default => throw new \InvalidArgumentException('Only JSON encoding is supported.'),
        };
    }

    final public static function deserialize(string $json, string $type, string $format = self::FORMAT_JSON): mixed
    {
        return match ($format) {
            self::FORMAT_JSON => JsonSerializer::deserializeValue($json, $type),
            default => throw new \InvalidArgumentException("Invalid format given: $format"),
        };
    }

    final public static function convertToType(mixed $value, string $type): string|int|bool|array|null|object|float
    {
        return match ($type) {
            'bool' => (bool) $value,
            'int' => (int) $value,
            'float' => (float) $value,
            'string' => (string) $value,
            'array' => (array) $value,
            'object' => (object) $value,
            'null' => null,
            default => self::handleEntityTypeConversions($value, $type),
        };
    }

    private static function handleEntityTypeConversions(array|object $value, string $type): array|object
    {
        preg_match(self::ARRAY_TYPE_EXPRESSION_MATCHER, $type, $arrayTypeMatches);
        if (!empty($arrayTypeMatches)) {
            $entityType = $arrayTypeMatches[1];

            return self::convertToArrayEntity((array) $value, $entityType);
        }

        return self::convertToEntity($value, $type);
    }

    private static function convertToArrayEntity(array $arrayValue, string $entityType): array
    {
        foreach ($arrayValue as &$item) {
            $item = self::convertToEntity($item, $entityType);
        }

        return $arrayValue;
    }

    private static function convertToEntity(object $value, string $type): object
    {
        // Casting to entity:
        $value = (array) $value;

        $reflectionClass = new \ReflectionClass($type);
        $object = $reflectionClass->newInstanceWithoutConstructor();

        foreach ($reflectionClass->getProperties() as $reflectionProperty) {
            $typeAttributes = $reflectionProperty->getAttributes(Type::class);
            if (1 === \count($typeAttributes)) {
                $type = $typeAttributes[0]->getArguments()[0];
            } elseif ([] === $typeAttributes) {
                continue;
            } else {
                throw new \RuntimeException('Property has multiple type attributes.');
            }
            $serializedNameAttributes = $reflectionProperty->getAttributes(SerializedName::class);
            if (1 === \count($serializedNameAttributes)) {
                $serializedName = $serializedNameAttributes[0]->getArguments()[0];
            } elseif ([] === $serializedNameAttributes) {
                continue;
            } else {
                throw new \RuntimeException('Property has multiple type attributes.');
            }

            if (isset($value[$serializedName])) {
                $reflectionProperty->setValue($object, self::convertToType($value[$serializedName], $type));
            }
        }

        return $object;
    }
}
