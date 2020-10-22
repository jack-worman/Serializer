<?php

namespace JWorman\Serializer;

use JWorman\AnnotationReader\AnnotationReader;
use JWorman\AnnotationReader\Exceptions\PropertyAnnotationNotFound;
use JWorman\Serializer\Annotations\SerializedName;
use JWorman\Serializer\Annotations\Type;

class Serializer
{
    const FORMAT_JSON = 'format-json';
    const ARRAY_TYPE_EXPRESSION_MATCHER = '/^(?:array<)(.*)(?:>)$/';

    /** @var AnnotationReader|null */
    private static $annotationReader;

    /**
     * @param mixed $payload
     * @param string $format
     * @param int $recursionLimit
     * @return string
     */
    final public static function serialize($payload, $format = self::FORMAT_JSON, $recursionLimit = 512)
    {
        if ($recursionLimit < 0) {
            throw new \InvalidArgumentException('The recursion limit cannot be negative.');
        }
        switch ($format) {
            case self::FORMAT_JSON:
                return JsonSerializer::serializeValue($payload, $recursionLimit);
            default:
                throw new \InvalidArgumentException('Only JSON encoding is supported.');
        }
    }

    /**
     * @param string $json
     * @param string $type
     * @param string $format
     * @return mixed
     */
    final public static function deserialize($json, $type, $format = self::FORMAT_JSON)
    {
        switch ($format) {
            case self::FORMAT_JSON:
                return JsonSerializer::deserializeValue($json, $type);
            default:
                throw new \InvalidArgumentException("Invalid format given: $format");
        }
    }

    /**
     * @param mixed $value
     * @param string $type
     * @return mixed
     */
    final public static function convertToType($value, $type)
    {
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
            default:
                return self::handleEntityTypeConversions($value, $type);
        }
    }

    /**
     * @param mixed $value
     * @param string $type
     * @return array|object
     */
    final private static function handleEntityTypeConversions($value, $type)
    {
        preg_match(self::ARRAY_TYPE_EXPRESSION_MATCHER, $type, $arrayTypeMatches);
        if (!empty($arrayTypeMatches)) {
            $entityType = $arrayTypeMatches[1];
            return self::convertToArrayEntity((array)$value, $entityType);
        }
        return self::convertToEntity($value, $type);
    }

    /**
     * @param array $arrayValue
     * @param string $entityType
     * @return array
     */
    final private static function convertToArrayEntity($arrayValue, $entityType)
    {
        foreach ($arrayValue as &$item) {
            $item = self::convertToEntity($item, $entityType);
        }
        return $arrayValue;
    }

    /**
     * @param mixed $value
     * @param string $type
     * @return object
     */
    final private static function convertToEntity($value, $type)
    {
        // Casting to entity:
        $value = (array)$value;

        $object = \unserialize(\sprintf('O:%d:"%s":0:{}', \strlen($type), $type));
        if (\get_class($object) === '__PHP_Incomplete_Class') {
            throw new \InvalidArgumentException('Invalid type given.');
        }

        try {
            $reflectionClass = new \ReflectionClass($object);
        } catch (\ReflectionException $e) {
            throw new \InvalidArgumentException("$type is unsupported.", 0, $e);
        }
        $annotationReader = self::getAnnotationReader();
        foreach ($reflectionClass->getProperties() as $reflectionProperty) {
            $annotationReader->getPropertyAnnotations($reflectionProperty);
            try {
                $type = $annotationReader
                    ->getPropertyAnnotation($reflectionProperty, Type::CLASS_NAME)
                    ->getValue();
                $serializedName = $annotationReader
                    ->getPropertyAnnotation($reflectionProperty, SerializedName::CLASS_NAME)
                    ->getValue();
            } catch (PropertyAnnotationNotFound $e) {
                continue;
            }
            if (isset($value[$serializedName])) {
                $reflectionProperty->setAccessible(true);
                $reflectionProperty->setValue($object, self::convertToType($value[$serializedName], $type));
            }
        }
        return $object;
    }

    /**
     * @return AnnotationReader
     */
    protected static function getAnnotationReader()
    {
        if (isset(self::$annotationReader)) {
            return self::$annotationReader;
        }
        self::$annotationReader = new AnnotationReader();
        return self::$annotationReader;
    }
}
