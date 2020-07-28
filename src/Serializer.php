<?php

/**
 * Serializer.php
 * @author Jack Worman
 */

namespace JWorman\Serializer;

use JWorman\AnnotationReader\Exceptions\AnnotationReaderException;

/**
 * Class Serializer
 * @package JWorman\Serializer
 */
class Serializer
{
    const ENCODING_TYPE_JSON = 'encoding-type-json';

    /**
     * @param mixed $payload
     * @param string $encodingType
     * @param int $recursionLimit
     * @return string
     */
    public static function serialize($payload, $encodingType = self::ENCODING_TYPE_JSON, $recursionLimit = 512)
    {
        if ($recursionLimit < 0) {
            throw new \InvalidArgumentException('The recursion limit cannot be negative.');
        }
        switch ($encodingType) {
            case self::ENCODING_TYPE_JSON:
                return JsonSerializer::serializePayload($payload, $recursionLimit);
            default:
                throw new \InvalidArgumentException('Only JSON encoding is supported.');
        }
    }

    /**
     * @param mixed $payload
     * @param string $type
     * @param string $encodingType
     * @return string
     */
    public static function deserialize($payload, $type, $encodingType = self::ENCODING_TYPE_JSON)
    {
        switch ($encodingType) {
            case self::ENCODING_TYPE_JSON:
                return JsonDeserializer::deserializePayload(preg_replace('/\s+/', '', $payload), $type);
            default:
                throw new \InvalidArgumentException('Only JSON encoding is supported.');
        }
    }

    /**
     * @param \stdClass|array $objectOrArray
     * @param string $type
     * @return mixed
     * @throws \ReflectionException
     * @throws AnnotationReaderException
     */
    public static function convertToClass($objectOrArray, $type)
    {
        $deserializedObject = JsonDeserializer::createInstanceWithoutConstructor($type);
        $reflectionClass = new \ReflectionClass($type);
        $deserializeMappings = JsonDeserializer::getDeserializeMappings($reflectionClass);
        foreach ($objectOrArray as $serializedPropertyName => $propertyValue) {
            $propertyName = $deserializeMappings[$serializedPropertyName];
            $reflectionProperty = $reflectionClass->getProperty($propertyName);
            $reflectionProperty->setAccessible(true);
            $reflectionProperty->setValue($deserializedObject, $propertyValue);
        }
        return $deserializedObject;
    }
}
