<?php

namespace JWorman\Serializer;

use JWorman\AnnotationReader\Exceptions\PropertyAnnotationNotFound;
use JWorman\Serializer\Annotations\SerializedName;

final class JsonSerializer extends Serializer
{
    const GET_TYPE_NULL = 'NULL';
    const GET_TYPE_BOOLEAN = 'boolean';
    const GET_TYPE_INTEGER = 'integer';
    const GET_TYPE_DOUBLE = 'double';
    const GET_TYPE_STRING = 'string';
    const GET_TYPE_ARRAY = 'array';
    const GET_TYPE_OBJECT = 'object';

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
        switch (\gettype($value)) {
            case self::GET_TYPE_NULL:
            case self::GET_TYPE_BOOLEAN:
            case self::GET_TYPE_INTEGER:
            case self::GET_TYPE_DOUBLE:
            case self::GET_TYPE_STRING:
                return self::serializePrimitive($value);
            case self::GET_TYPE_ARRAY:
                if (self::isAssociativeArray($value)) {
                    return self::serializeStdClass((object)$value, $recursionLimit - 1);
                } else {
                    return self::serializeArray($value, $recursionLimit - 1);
                }
            case self::GET_TYPE_OBJECT:
                if (\get_class($value) === 'stdClass') {
                    return self::serializeStdClass($value, $recursionLimit - 1);
                } else {
                    return self::serializeEntity($value, $recursionLimit - 1);
                }
            default:
                throw new \InvalidArgumentException('Unsupported type given: "' . \gettype($value) . '"');
        }
    }

    /**
     * @param string $json
     * @param string $type
     * @return mixed
     */
    protected static function deserializeValue($json, $type)
    {
        $decodedJson = \json_decode($json);
        if (\json_last_error() !== \JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('Invalid JSON given.');
        }
        return Serializer::convertToType($decodedJson, $type);
    }

    /**
     * @param null|bool|int|float|string $primitive
     * @return string
     */
    private static function serializePrimitive($primitive)
    {
        $serializedPrimitive = \json_encode($primitive);
        if ($serializedPrimitive === false) {
            throw new \InvalidArgumentException('Could not encode into entity.');
        }
        return $serializedPrimitive;
    }

    /**
     * @param array<mixed> $array
     * @param int $recursionLimit
     * @return string
     */
    private static function serializeArray(array $array, $recursionLimit)
    {
        $values = array();
        foreach ($array as $value) {
            $values[] = self::serializeValue($value, $recursionLimit);
        }
        return '[' . \implode(',', $values) . ']';
    }

    /**
     * @param \stdClass $stdClass
     * @param int $recursionLimit
     * @return string
     */
    private static function serializeStdClass(\stdClass $stdClass, $recursionLimit)
    {
        $properties = array();
        foreach ((array)$stdClass as $key => $value) {
            $properties[] = \json_encode($key) . ':' . self::serializeValue($value, $recursionLimit);
        }
        return '{' . \implode(',', $properties) . '}';
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
            throw new \InvalidArgumentException($e->getMessage(), 0, $e);
        }
        $properties = array();
        $annotationReader = self::getAnnotationReader();
        foreach ($reflectionClass->getProperties() as $reflectionProperty) {
            $reflectionProperty->setAccessible(true);
            try {
                $serializedName = $annotationReader
                    ->getPropertyAnnotation($reflectionProperty, SerializedName::CLASS_NAME)
                    ->getValue();
            } catch (PropertyAnnotationNotFound $e) {
                continue;
            }
            $properties[] = \json_encode($serializedName) . ':'
                . self::serializeValue($reflectionProperty->getValue($entity), $recursionLimit);
        }
        return '{' . implode(',', $properties) . '}';
    }

    /**
     * @param array<mixed> $array
     * @return bool
     */
    private static function isAssociativeArray(array $array)
    {
        if (empty($array)) {
            return false;
        }
        return \array_keys($array) !== \range(0, \count($array) - 1);
    }
}
