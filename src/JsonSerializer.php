<?php

/**
 * JsonSerializer.php
 * @author Jack Worman
 */

namespace JWorman\Serializer;

use JWorman\AnnotationReader\AnnotationReader;
use JWorman\AnnotationReader\Exceptions\AnnotationReaderException;
use JWorman\Serializer\Annotations\SerializedName;

/**
 * Class JsonSerializer
 * @package JWorman\Serializer
 */
final class JsonSerializer extends Serializer
{
    /**
     * @param mixed $payload
     * @param int $recursionLimit
     * @return string
     */
    protected static function serializePayload($payload, $recursionLimit)
    {
        if ($recursionLimit === -1) {
            throw new \RuntimeException('Recursion limit exceeded.');
        }
        switch (gettype($payload)) {
            case 'boolean':
                return $payload ? 'true' : 'false';
            case 'integer':
            case 'double':
                return $payload;
            case 'string':
                return '"' . $payload . '"';
            case 'array':
                return self::serializeArray($payload, $recursionLimit - 1);
            case 'object':
                return (
                get_class($payload) === 'stdClass'
                    ? self::serializeStdClass($payload, $recursionLimit - 1)
                    : self::serializeEntity($payload, $recursionLimit - 1)
                );
            case 'NULL':
                return 'null';
            default:
                throw new \InvalidArgumentException('Unsupported type given: "' . gettype($payload) . '"');
        }
    }

    /**
     * @param array $array
     * @param int $recursionLimit
     * @return string
     */
    private static function serializeArray(array $array, $recursionLimit)
    {
        if (self::isAssociativeArray($array)) {
            return self::serializeStdClass((object)$array, $recursionLimit);
        }
        $values = array();
        foreach ($array as $value) {
            $values[] = self::serializePayload($value, $recursionLimit);
        }
        return '[' . implode(',', $values) . ']';
    }

    /**
     * @param \stdClass $stdClass
     * @param int $recursionLimit
     * @return string
     */
    private static function serializeStdClass(\stdClass $stdClass, $recursionLimit)
    {
        $properties = array();
        foreach ($stdClass as $key => $value) {
            $properties[] = '"' . $key . '":' . self::serializePayload($value, $recursionLimit);
        }
        return '{' . implode(',', $properties) . '}';
    }

    /**
     * @param object $entity
     * @param int $recursionLimit
     * @return string
     */
    private static function serializeEntity($entity, $recursionLimit)
    {
        try {
            $reflectionClass = new \ReflectionClass($entity);
        } catch (\ReflectionException $e) {
            throw new \LogicException($e->getMessage(), 0, $e);
        }
        $properties = array();
        foreach ($reflectionClass->getProperties() as $reflectionProperty) {
            $reflectionProperty->setAccessible(true);
            // TODO: I am not sure about this try/catch.
            try {
                /** @var SerializedName $serializedNameAnnotation */
                $serializedNameAnnotation = AnnotationReader::getPropertyAnnotation(
                    $reflectionProperty,
                    SerializedName::CLASS_NAME
                );
                $key = $serializedNameAnnotation->getName();
            } catch (AnnotationReaderException $e) {
                $key = $reflectionProperty->getName();
            }
            $properties[] = '"' . $key . '":'
                . self::serializePayload($reflectionProperty->getValue($entity), $recursionLimit);
        }
        return '{' . implode(',', $properties) . '}';
    }

    /**
     * @param array $array
     * @return bool
     */
    private static function isAssociativeArray(array $array)
    {
        if (empty($array)) {
            return false;
        }
        return array_keys($array) !== range(0, count($array) - 1);
    }
}
