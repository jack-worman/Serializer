<?php

/**
 * Serializer.php
 * @author Jack Worman
 */

namespace JWorman\Serializer;

use JWorman\AnnotationReader\AnnotationReader;
use JWorman\AnnotationReader\Exceptions\AnnotationReaderException;
use JWorman\Serializer\Annotations\SerializedName;

/**
 * Class Serializer
 * @package JWorman\Serializer
 */
class Serializer
{
//    /**
//     * Creates a stdClass and puts the property values of the object on it with their serialized names.
//     *
//     * @param $object
//     * @return string
//     * @throws AnnotationReaderException
//     * @throws \ReflectionException
//     */
//    public static function serializeEntity($object)
//    {
//        $serializedObject = new \stdClass();
//        $reflectionClass = new \ReflectionClass($object);
//        foreach ($reflectionClass->getProperties() as $reflectionProperty) {
//            $reflectionProperty->setAccessible(true);
//            /** @var SerializedName $serializedNameAnnotation */
//            $serializedNameAnnotation = AnnotationReader::getPropertyAnnotation(
//                $reflectionProperty,
//                SerializedName::CLASS_NAME
//            );
//            $serializedObject->{$serializedNameAnnotation->getName()} = $reflectionProperty->getValue($object);
//        }
//        return json_encode($serializedObject);
//    }

    /**
     * @param mixed $payload
     * @param string $encoding
     * @param int $recursionLimit
     * @return string
     */
    public static function serialize($payload, $encoding = 'json', $recursionLimit = 512)
    {
        if ($encoding !== 'json') {
            throw new \InvalidArgumentException('Currently only supports JSON encoding.');
        }
        return substr(self::serializePayload($payload), 0, -1);
    }

    /**
     * @param mixed $payload
     * @return string
     */
    private static function serializePayload($payload)
    {
        switch (gettype($payload)) {
            case 'boolean':
                return $payload ? 'true,' : 'false,';
            case 'integer':
            case 'double':
                return $payload . ',';
            case 'string':
                return '"' . $payload . '",';
            case 'array':
                return self::serializeArray($payload) . ',';
            case 'object':
                return (
                    get_class($payload) === 'stdClass'
                        ? self::serializeStdClass($payload)
                        : self::serializeEntity($payload)
                    ) . ',';
            case 'NULL':
                return 'null,';
            default:
                throw new \InvalidArgumentException('Unsupported type given.');
        }
    }

    /**
     * @param object $entity
     * @return string
     */
    private static function serializeEntity($entity)
    {
        try {
            $reflectionClass = new \ReflectionClass($entity);
        } catch (\ReflectionException $e) {
            throw new \LogicException($e->getMessage(), 0, $e);
        }
        $serializedEntity = '{';
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
            $serializedEntity .= '"' . $key . '":' . self::serializePayload($reflectionProperty->getValue($entity));
        }
        return substr($serializedEntity, 0, -1) . '}';
    }

    /**
     * @param array $array
     * @return string
     */
    private static function serializeArray(array $array)
    {
        if (empty($array)) {
            return '[]';
        } elseif (self::isAssociativeArray($array)) {
            return self::serializeStdClass((object)$array);
        }
        $serializedArray = '[';
        foreach ($array as $value) {
            $serializedArray .= self::serializePayload($value);
        }
        return substr($serializedArray, 0, -1) . ']';
    }

    /**
     * @param \stdClass $stdClass
     * @return string
     */
    private static function serializeStdClass(\stdClass $stdClass)
    {
        if (count((array)$stdClass) === 0) {
            return '{}';
        }
        $serializedStdClass = '{';
        foreach ($stdClass as $key => $value) {
            $serializedStdClass .= '"' . $key . '":' . self::serializePayload($value);
        }
        return substr($serializedStdClass, 0, -1) . '}';
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




    /**
     * @param string $serializedObject
     * @param string $type
     * @return mixed
     */
    public static function deserialize($serializedObject, $type)
    {
        return self::convertToClass(json_decode($serializedObject), $type);
    }

    /**
     * @param \stdClass|array $objectOrArray
     * @param string $type
     * @return mixed
     * @throws \ReflectionException
     */
    public static function convertToClass($objectOrArray, $type)
    {
        $deserializedObject = self::createInstanceWithoutConstructor($type);
        $reflectionClass = new \ReflectionClass($type);
        $deserializeMappings = self::getDeserializeMappings($reflectionClass);
        foreach ($objectOrArray as $serializedPropertyName => $propertyValue) {
            $propertyName = $deserializeMappings[$serializedPropertyName];
            $reflectionProperty = $reflectionClass->getProperty($propertyName);
            $reflectionProperty->setAccessible(true);
            $reflectionProperty->setValue($deserializedObject, $propertyValue);
        }
        return $deserializedObject;
    }

    /**
     * @param \ReflectionClass $reflectionClass
     * @return string[]
     * @throws AnnotationReaderException
     */
    private static function getDeserializeMappings(\ReflectionClass $reflectionClass)
    {
        $deserializeMappings = array();
        foreach ($reflectionClass->getProperties() as $reflectionProperty) {
            /** @var SerializedName $serializedName */
            $serializedName = AnnotationReader::getPropertyAnnotation(
                $reflectionProperty,
                SerializedName::CLASS_NAME
            );
            $deserializeMappings[$serializedName->getName()] = $reflectionProperty->getName();
        }
        return $deserializeMappings;
    }

    /**
     * Using unserialize() because ReflectionClass's newInstanceWithoutConstructor() is not available in this
     * version of PHP.
     * See: https://stackoverflow.com/a/2556089
     *
     * @param string $type
     * @return mixed
     */
    private static function createInstanceWithoutConstructor($type)
    {
        return unserialize(sprintf('O:%d:"%s":0:{}', strlen($type), $type));
    }
}
