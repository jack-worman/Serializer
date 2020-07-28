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
    private $i;
    private $json;

    public function startDeserializeJson($json)
    {
        $this->i = 0;
        $this->json = $json;
        return $this->deserializeValue();
    }

    /**
     * @return mixed
     */
    private function deserializeValue()
    {
        switch ($this->json[$this->i]) {
            case '{':
                return $this->deserializeObject();
            case '[':
                return $this->deserializeArray();
            case '"':
                return $this->deserializeString();
            case '-':
            case '0':
            case '1':
            case '2':
            case '3':
            case '4':
            case '5':
            case '6':
            case '7':
            case '8':
            case '9':
                return $this->deserializeNumber();
            case 't':
                $this->i += 3; // Consumes 'true'
                return true;
            case 'f':
                $this->i += 4; // Consumes 'false'
                return false;
            case 'n':
                $this->i += 3; // Consumes 'null'
                return null;
            default:
                throw new \InvalidArgumentException('Invalid JSON given.');
        }
    }

    /**
     * @return \stdClass
     */
    private function deserializeObject()
    {
        $stdClass = new \stdClass();
        for ($this->i++; $this->i < strlen($this->json); $this->i++) {
            switch ($this->json[$this->i]) {
                case ',':
                    break;
                case '}':
                    return $stdClass;
                case '"';
                    $value = $this->deserializeValue();
                    if ($this->json[$this->i + 1] === ':') {
                        $this->i += 2;
                        $stdClass->{$value} = $this->deserializeValue();
                        break;
                    }
                default:
                    throw new \InvalidArgumentException('No value for property.');
            }
        }
        throw new \InvalidArgumentException('Reached end of JSON while in an object.');
    }

    /**
     * @return array
     */
    private function deserializeArray()
    {
        $array = array();
        for ($this->i++; $this->i < strlen($this->json); $this->i++) {
            switch ($this->json[$this->i]) {
                case ',':
                    break;
                case ']':
                    return $array;
                case '"';
                    $value = $this->deserializeValue();
                    if ($this->json[$this->i + 1] === ':') {
                        $this->i += 2;
                        $array[$value] = $this->deserializeValue();
                    } else {
                        $array[] = $value;
                    }
                    break;
                default:
                    $array[] = $this->deserializeValue();
            }
        }
        throw new \InvalidArgumentException('Reached end of JSON while in an array.');
    }

    /**
     * @return string
     */
    private function deserializeString()
    {
        $string = '';
        $escapeActive = false;
        for ($this->i++; $this->i < strlen($this->json); $this->i++) {
            if ($escapeActive) {
                switch ($this->json[$this->i]) {
                    case '"':
                        $string .= '"';
                        break;
                    case '\\':
                        $string .= '\\';
                        break;
                    case '/':
                        $string .= '/';
                        break;
                    case 'b':
                        $string .= chr(8);
                        break;
                    case 'f':
                        $string .= "\f";
                        break;
                    case 'n':
                        $string .= "\n";
                        break;
                    case 'r':
                        $string .= "\r";
                        break;
                    case 't':
                        $string .= "\t";
                        break;
                    case 'u':
                        $unicode = json_decode('"\u' . substr($this->json, $this->i + 1, 4) . '"');
                        if ($unicode === null) {
                            throw new \InvalidArgumentException('Invalid unicode given.');
                        }
                        $string .= $unicode;
                        $this->i += 4;
                        break;
                    default:
                        throw new \InvalidArgumentException('Invalid string given.');
                }
                $escapeActive = false;
            } else {
                switch ($this->json[$this->i]) {
                    case '\\':
                        $escapeActive = true;
                        break;
                    case '"':
                        return $string;
                    default:
                        if (ord($this->json[$this->i]) >= 32 && ord($this->json[$this->i]) <= 127) {
                            $string .= $this->json[$this->i];
                        } else {
                            throw new \InvalidArgumentException('Invalid string: ASCII must be between 32 and 128.');
                        }
                }
            }
        }
        throw new \InvalidArgumentException('Reached end while in a string.');
    }

    /**
     * @return int|float
     */
    private function deserializeNumber()
    {
        $startingPosition = $this->i;
        for ($this->i++; $this->i < strlen($this->json); $this->i++) {
            if ($this->json[$this->i] === ',' || $this->json[$this->i] === ']' || $this->json[$this->i] === '}') {
                $number = json_decode(substr($this->json, $startingPosition, $this->i - $startingPosition));
                if ($number === null) {
                    throw new \InvalidArgumentException('Invalid number given.');
                }
                $this->i--; // Unconsumes the comma.
                return $number;
            }
        }
        // No comma if number is only thing encoded.
        if ($startingPosition === 0) {
            $number = json_decode($this->json);
            if ($number === null) {
                throw new \InvalidArgumentException('Invalid number given.');
            }
            return $number;
        }
        throw new \InvalidArgumentException('Reached end of JSON while parsing number.');
    }


    /**
     * @param string $payload
     * @param string $type
     * @return mixed
     */
    protected static function deserializePayload($payload, $type)
    {
        switch ($type) {
            case 'bool':
                return self::deserializeBool($payload);
            case 'int':
                return (int)$payload;
            case 'float':
                return (float)$payload;
            case 'string':
                return $payload;
            case 'array':
                // deserializeArray()
            case 'stdClass':
                // deserializeObject()
            case 'null':
                return null;
            default:
                try {
                    $reflectionClass = new \ReflectionClass($type);
                    // deserializeEntity()
                    return 'null';
                } catch (\ReflectionException $e) {
                    throw new \InvalidArgumentException('Unsupported type given.');
                }
        }
    }

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
