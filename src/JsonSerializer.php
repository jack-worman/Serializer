<?php

namespace JWorman\Serializer;

use JWorman\Serializer\Attribute\SerializedName;

final class JsonSerializer extends Serializer
{
    protected static function serializeValue(mixed $value, int $recursionLimit): string
    {
        if (-1 === $recursionLimit) {
            throw new \RuntimeException('Recursion limit exceeded.');
        }
        if (null === $value || is_scalar($value)) {
            return self::serializeScalar($value);
        } elseif (\is_array($value)) {
            if (array_is_list($value)) {
                return self::serializeList($value, $recursionLimit - 1);
            } else {
                return self::serializeStdClass((object) $value, $recursionLimit - 1);
            }
        } elseif (\is_object($value)) {
            if (\stdClass::class === $value::class) {
                return self::serializeStdClass($value, $recursionLimit - 1);
            } else {
                return self::serializeEntity($value, $recursionLimit - 1);
            }
        } else {
            throw new \InvalidArgumentException('Unsupported type given: "'.get_debug_type($value).'"');
        }
    }

    protected static function deserializeValue(string $json, string $type): mixed
    {
        $decodedJson = json_decode($json);
        if (\JSON_ERROR_NONE !== json_last_error()) {
            throw new \InvalidArgumentException('Invalid JSON given.');
        }

        return Serializer::convertToType($decodedJson, $type);
    }

    private static function serializeScalar(null|bool|int|float|string $scalar): string
    {
        $serializeScalar = json_encode($scalar, \JSON_THROW_ON_ERROR);
        if (false === $serializeScalar) {
            throw new \LogicException();
        }

        return $serializeScalar;
    }

    private static function serializeList(array $array, int $recursionLimit): string
    {
        $values = array_map(
            fn (mixed $value) => self::serializeValue($value, $recursionLimit),
            $array
        );

        return sprintf('[%s]', implode(',', $values));
    }

    private static function serializeStdClass(\stdClass $stdClass, int $recursionLimit): string
    {
        $properties = [];
        foreach ((array) $stdClass as $key => $value) {
            $properties[] = sprintf(
                '%s:%s',
                json_encode($key, \JSON_THROW_ON_ERROR),
                self::serializeValue($value, $recursionLimit)
            );
        }

        return sprintf('{%s}', implode(',', $properties));
    }

    private static function serializeEntity(object $entity, int $recursionLimit): string
    {
        $reflectionClass = new \ReflectionClass($entity);
        $properties = [];
        foreach ($reflectionClass->getProperties() as $reflectionProperty) {
            $serializedNameAttributes = $reflectionProperty->getAttributes(SerializedName::class);
            if ([] === $serializedNameAttributes) {
                continue;
            } elseif (1 === \count($serializedNameAttributes)) {
                $serializedName = $serializedNameAttributes[0]->getArguments()[0];
            } else {
                throw new \RuntimeException();
            }

            $value = $reflectionProperty->isInitialized($entity)
                ? $reflectionProperty->getValue($entity)
                : null;
            $properties[] = sprintf(
                '%s:%s',
                json_encode($serializedName, \JSON_THROW_ON_ERROR),
                self::serializeValue($value, $recursionLimit)
            );
        }

        return sprintf('{%s}', implode(',', $properties));
    }
}
