<?php

/**
 * Serializer.php
 * @author Jack Worman
 */

namespace JWorman\Serializer;

use JWorman\AnnotationReader\AnnotationReader;
use JWorman\AnnotationReader\Exceptions\PropertyAnnotationNotFound;
use JWorman\Serializer\Annotations\SerializedName;
use JWorman\Serializer\Annotations\Type;

/**
 * Class Serializer
 * @package JWorman\Serializer
 */
class Serializer
{
    const FORMAT_JSON = 'encoding-type-json';

    /**
     * @var AnnotationReader|null
     */
    private static $annotationReader = null;

    /**
     * @param mixed $payload
     * @param string $encodingType
     * @param int $recursionLimit
     * @return string
     */
    public static function serialize($payload, $encodingType = self::FORMAT_JSON, $recursionLimit = 512)
    {
        if ($recursionLimit < 0) {
            throw new \InvalidArgumentException('The recursion limit cannot be negative.');
        }
        switch ($encodingType) {
            case self::FORMAT_JSON:
                return JsonSerializer::serializeValue($payload, $recursionLimit);
            default:
                throw new \InvalidArgumentException('Only JSON encoding is supported.');
        }
    }

    /**
     * @param mixed $json
     * @param string $type
     * @param string $format
     * @return mixed
     */
    public static function deserialize($json, $type, $format = self::FORMAT_JSON)
    {
        switch ($format) {
            case self::FORMAT_JSON:
                $decodedJson = \json_decode($json);
                if (\json_last_error() !== JSON_ERROR_NONE) {
                    throw new \InvalidArgumentException('Invalid JSON given.');
                }
                return self::convertToType($decodedJson, $type);
            default:
                throw new \InvalidArgumentException('Only JSON encoding is supported.');
        }
    }

    /**
     * @param mixed $value
     * @param string $type
     * @return mixed
     */
    public static function convertToType($value, $type)
    {
        // Casting to primitive:
        switch ($type) {
            case 'bool':
                return (bool)$value;
            case 'int':
                return (int)$value;
            case 'float':
                return (float)$value;
            case 'string':
                return (string)$value;
            case 'array':
                return (array)$value;
            case 'object':
                return (object)$value;
            case 'null':
                return null;
        }
        // Casting to entity:
        $value = (array)$value;

        $object = \unserialize(\sprintf('O:%d:"%s":0:{}', \strlen($type), $type));
        if (\get_class($object) === '__PHP_Incomplete_Class') {
            throw new \InvalidArgumentException('Invalid type given.');
        }

        try {
            $reflectionClass = new \ReflectionClass($object);
        } catch (\ReflectionException $e) {
            throw new \RuntimeException("$type is unsupported.", 0, $e);
        }
        self::$annotationReader = self::$annotationReader ?: new AnnotationReader();
        foreach ($reflectionClass->getProperties() as $reflectionProperty) {
            try {
                $type = self::$annotationReader
                    ->getPropertyAnnotation($reflectionProperty, Type::CLASS_NAME)
                    ->getValue();
                $serializedName = self::$annotationReader
                    ->getPropertyAnnotation($reflectionProperty, SerializedName::CLASS_NAME)
                    ->getValue();
            } catch (PropertyAnnotationNotFound $e) {
                throw new \Exception('Type and SerializedName annotations must be defined.', 0, $e);
            }
            if (isset($value[$serializedName])) {
                $reflectionProperty->setAccessible(true);
                $reflectionProperty->setValue($object, self::convertToType($value[$serializedName], $type));
            }
        }
        return $object;
    }
}
