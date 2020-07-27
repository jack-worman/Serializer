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

//    /**
//     * @param string $payload
//     * @param string $type
//     * @param string $encodingType
//     * @return mixed
//     */
//    public static function deserialize($payload, $type, $encodingType = self::ENCODING_TYPE_JSON)
//    {
//        var_dump($payload);
////        $firstLetter = substr($serializedObject, 0, 1);
////        switch ($serializedObject[0]) {
////            case '{':
////                // object
////                break;
////            case '[':
////                // array
////                break;
////            case '"':
////                return substr($serializedObject, 0, strpos($serializedObject, '"', 1) + 1);
////            case 't':
////                return true;
////            case 'f':
////                return false;
////            case 'n':
////                return null;
////            default:
////                throw new \InvalidArgumentException('Invalid JSON.');
////        }
//        switch ($type) {
//            case 'bool':
//                return (bool)$payload;
//            case 'int':
//                return (int)$payload;
//            case 'float':
//                return (float)$payload;
//            case 'string':
//                return (string)$payload;
//            case 'array':
////                return self::serializeArray($payload, $recursionLimit - 1) . ',';
//            case 'object':
////                return (
////                    get_class($payload) === 'stdClass'
////                        ? self::serializeStdClass($payload, $recursionLimit - 1)
////                        : self::serializeEntity($payload, $recursionLimit - 1)
////                    ) . ',';
//            case 'null':
//                return null;
//            default:
//                throw new \InvalidArgumentException('Unsupported type given.');
//        }
//    }

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
