<?php

/**
 * JsonSerializer.php
 * @author Jack Worman
 */

namespace JWorman\Serializer;

use JWorman\AnnotationReader\AnnotationReader;
use JWorman\Serializer\Annotations\SerializedName;

/**
 * Class JsonSerializer
 * @package JWorman\Serializer
 */
final class JsonSerializer extends Serializer
{
    /**
     * @param mixed $value
     * @param int $recursionLimit
     * @return string
     */
    protected static function serializeValue($value, $recursionLimit)
    {
        if ($recursionLimit === -1) {
            throw new \RuntimeException('Recursion limit exceeded.');
        }
        switch (gettype($value)) {
            case 'boolean':
                return $value ? 'true' : 'false';
            case 'integer':
            case 'double':
                return (string)$value;
            case 'string':
                return json_encode($value);
            case 'array':
                return self::serializeArray($value, $recursionLimit - 1);
            case 'object':
                if (get_class($value) === 'stdClass') {
                    return self::serializeStdClass($value, $recursionLimit - 1);
                } else {
                    return self::serializeEntity($value, $recursionLimit - 1);
                }
            case 'NULL':
                return 'null';
            default:
                throw new \InvalidArgumentException('Unsupported type given: "' . gettype($value) . '"');
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
            $values[] = self::serializeValue($value, $recursionLimit);
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
            $properties[] = json_encode($key) . ':' . self::serializeValue($value, $recursionLimit);
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
        $annotationReader = new AnnotationReader();
        foreach ($reflectionClass->getProperties() as $reflectionProperty) {
            $reflectionProperty->setAccessible(true);
            try {
                $serializedName = $annotationReader
                    ->getPropertyAnnotation($reflectionProperty, SerializedName::CLASS_NAME)
                    ->getValue();
            } catch (\Exception $e) {
                throw new \InvalidArgumentException('Must have SerializedName annotation.', 0, $e);
            }
            $properties[] = json_encode($serializedName) . ':'
                . self::serializeValue($reflectionProperty->getValue($entity), $recursionLimit);
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
