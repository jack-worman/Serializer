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
//    public static function serialize($object)
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
     * Creates a stdClass and puts the property values of the object on it with their serialized names.
     *
     * @param $object
     * @return string
     * @throws \ReflectionException
     */
    public static function serialize($object)
    {
        // TODO: Check type here and send to proper serializer (i.e. arrays go to array serializer)

        $serializedObject = "{";
        $reflectionClass = new \ReflectionClass($object);
        foreach ($reflectionClass->getProperties() as $reflectionProperty) {
            $reflectionProperty->setAccessible(true);
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

            $serializedObject .= "\"$key\":" . self::buildValue($reflectionProperty->getValue($object));
        }
        return substr($serializedObject, 0, -1) . '}';
    }

    private static function isAssoc(array $array)
    {
        if (array() === $array) {
            return false;
        }
        return array_keys($array) !== range(0, count($array) - 1);
    }

    private static function serializeArray(array $array)
    {
        if (empty($array)) {
            return '[]';
        } elseif (self::isAssoc($array)) {
            return self::serializeStdClass($array);
        }
        $serializedArray = '[';
        foreach ($array as $value) {
            $serializedArray .= self::buildValue($value);
        }
        $serializedArray = substr($serializedArray, 0, -1) . ']';
        return $serializedArray;
    }

    private static function serializeStdClass($stdClass)
    {
        if (count((array)$stdClass) === 0) {
            return '{}';
        }
        $serializedStdClass = '{';
        foreach ($stdClass as $key => $value) {
            $serializedStdClass .= "\"$key\":" . self::buildValue($value);
        }
        $serializedStdClass = substr($serializedStdClass, 0, -1) . '}';
        return $serializedStdClass;
    }

    private static function buildValue($value)
    {
        $serializedValue = '';
        switch (gettype($value)) {
            case 'boolean':
                $serializedValue .= ($value ? 'true' : 'false');
                break;
            case 'integer':
            case 'double':
                $serializedValue .= "$value";
                break;
            case 'string':
                $serializedValue .= "\"$value\"";
                break;
            case 'array':
                $serializedValue .= self::serializeArray($value);
                break;
            case 'object':
                if (get_class($value) === 'stdClass') {
                    $serializedValue .= self::serializeStdClass($value);
                } else {
                    $serializedValue .= self::serialize($value);
                }
                break;
            case 'NULL':
                $serializedValue .= 'null';
                break;
        }
        return "$serializedValue,";
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
