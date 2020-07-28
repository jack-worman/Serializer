<?php

/**
 * Serializer.php
 * @author Jack Worman
 */

namespace JWorman\Serializer;

use JWorman\AnnotationReader\AnnotationReader;
use JWorman\AnnotationReader\Exceptions\AnnotationReaderException;
use JWorman\Serializer\Annotations\SerializedName;
use JWorman\Serializer\Annotations\Type;

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
     * @param mixed $json
     * @param string $type
     * @param string $encodingType
     * @return string
     */
    public static function deserialize($json, $type, $encodingType = self::ENCODING_TYPE_JSON)
    {
        switch ($encodingType) {
            case self::ENCODING_TYPE_JSON:
                $decodedJson = json_decode($json);
                if (json_last_error()) {
                    throw new \InvalidArgumentException('Invalid JSON given.');
                }
                return self::castToType($decodedJson, $type);
            default:
                throw new \InvalidArgumentException('Only JSON encoding is supported.');
        }
    }

    /**
     * @param mixed $value
     * @param string $type
     * @return mixed
     */
    public static function castToType($value, $type)
    {
        // Casting to primitive:
        switch ($type) {
            case 'bool':
            case 'int':
            case 'float':
            case 'string':
            case 'array':
            case 'object':
            case 'null':
                if (!settype($value, $type)) {
                    throw new \InvalidArgumentException("Could not cast to a $type.");
                }
                return $value;
        }
        // Casting to entity:
        $value = (array)$value;

        $object = unserialize(sprintf('O:%d:"%s":0:{}', strlen($type), $type));
        if (get_class($object) === '__PHP_Incomplete_Class') {
            throw new \InvalidArgumentException('Invalid type given.');
        }

        try {
            $reflectionClass = new \ReflectionClass($object);
        } catch (\ReflectionException $e) {
            throw new \LogicException('This shouldn\'t be possible.');
        }
        foreach ($reflectionClass->getProperties() as $reflectionProperty) {
            try {
                $type = self::getType($reflectionProperty);
            } catch (AnnotationReaderException $e) {
                throw new \RuntimeException($e->getMessage(), 0, $e);
            }
            $serializedName = self::getSerializedName($reflectionProperty);
            if (isset($value[$serializedName])) {
                $reflectionProperty->setAccessible(true);
                $reflectionProperty->setValue($object, self::castToType($value[$serializedName], $type));
            }
        }
        return $object;
    }

    /**
     * @param \ReflectionProperty $reflectionProperty
     * @return string
     */
    private static function getSerializedName(\ReflectionProperty $reflectionProperty)
    {
        try {
            /** @var SerializedName $serializedNameAnnotation */
            $serializedNameAnnotation = AnnotationReader::getPropertyAnnotation(
                $reflectionProperty,
                SerializedName::CLASS_NAME
            );
            return $serializedNameAnnotation->getName();
        } catch (\Exception $e) {
            return $reflectionProperty->getName();
        }
    }

    /**
     * @param \ReflectionProperty $reflectionProperty
     * @return string
     * @throws AnnotationReaderException
     */
    private static function getType(\ReflectionProperty $reflectionProperty)
    {
        /** @var Type $typeAnnotation */
        $typeAnnotation = AnnotationReader::getPropertyAnnotation(
            $reflectionProperty,
            Type::CLASS_NAME
        );
        return $typeAnnotation->getType();
    }
}
