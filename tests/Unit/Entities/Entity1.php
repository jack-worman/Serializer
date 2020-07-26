<?php

/**
 * Entity1.php
 * @author Jack Worman
 */

namespace JWorman\Serializer\Tests\Unit\Entities;

use JWorman\Serializer\Annotations\SerializedName;

/**
 * Class Entity1
 * @package JWorman\Serializer\Tests\Unit\Entities
 */
class Entity1
{
    const CLASS_NAME = __CLASS__;

    /**
     * @var null
     * @SerializedName("null_property")
     */
    private $null;

    /**
     * @var bool
     * @SerializedName("bool_property")
     */
    private $bool;

    /**
     * @var int
     * @SerializedName("int_property")
     */
    private $int;

    /**
     * @var float
     * @SerializedName("float_property")
     */
    private $float;

    /**
     * @var string
     * @SerializedName("string_property")
     */
    private $string;

    /**
     * @var array
     * @SerializedName("array_property")
     */
    private $array;

    /**
     * @var array
     * @SerializedName("associative_array_property")
     */
    private $associativeArray;

    /**
     * @var \stdClass
     * @SerializedName("stdClass_property")
     */
    private $stdClass;

    /**
     * @var Entity1
     * @SerializedName("entity_property")
     */
    private $entity;

    /**
     * @param null $null
     * @return Entity1
     */
    public function setNull($null)
    {
        $this->null = $null;
        return $this;
    }

    /**
     * @param bool $bool
     * @return Entity1
     */
    public function setBool($bool)
    {
        $this->bool = $bool;
        return $this;
    }

    /**
     * @param int $int
     * @return Entity1
     */
    public function setInt($int)
    {
        $this->int = $int;
        return $this;
    }

    /**
     * @param float $float
     * @return Entity1
     */
    public function setFloat($float)
    {
        $this->float = $float;
        return $this;
    }

    /**
     * @param string $string
     * @return Entity1
     */
    public function setString($string)
    {
        $this->string = $string;
        return $this;
    }

    /**
     * @param array $array
     * @return Entity1
     */
    public function setArray($array)
    {
        $this->array = $array;
        return $this;
    }

    /**
     * @param array $associativeArray
     * @return Entity1
     */
    public function setAssociativeArray($associativeArray)
    {
        $this->associativeArray = $associativeArray;
        return $this;
    }

    /**
     * @param \stdClass $stdClass
     * @return Entity1
     */
    public function setStdClass($stdClass)
    {
        $this->stdClass = $stdClass;
        return $this;
    }

    /**
     * @param Entity1 $entity
     * @return Entity1
     */
    public function setEntity($entity)
    {
        $this->entity = $entity;
        return $this;
    }
}