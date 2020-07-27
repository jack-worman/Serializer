<?php

/**
 * JsonDeserializer.php
 * @author Jack Worman
 */

namespace JWorman\Serializer;

use JWorman\AnnotationReader\AnnotationReader;
use JWorman\AnnotationReader\Exceptions\AnnotationReaderException;
use JWorman\Serializer\Annotations\SerializedName;

/**
 * Class JsonDeserializer
 * @package JWorman\Serializer
 */
class JsonDeserializer extends Serializer
{
    /**
     * @param \ReflectionClass $reflectionClass
     * @return string[]
     * @throws AnnotationReaderException
     */
    protected static function getDeserializeMappings(\ReflectionClass $reflectionClass)
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
    protected static function createInstanceWithoutConstructor($type)
    {
        return unserialize(sprintf('O:%d:"%s":0:{}', strlen($type), $type));
    }
}
