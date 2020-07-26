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
     * @SerializedName("null")
     */
    private $null = null;

    /**
     * @var bool
     * @SerializedName("bool")
     */
    private $bool = false;

    /**
     * @var int
     * @SerializedName("int")
     */
    private $int = 42;

    /**
     * @var float
     * @SerializedName("float")
     */
    private $float = 3.14;

    /**
     * @var string
     * @SerializedName("string")
     */
    private $string = 'fizzbuzz';

    /**
     * @var array
     * @SerializedName("empty_array")
     */
    private $emptyArray = array();

    /**
     * @var array|null
     * @SerializedName("array")
     */
    private $array;

    /**
     * @var array|null
     * @SerializedName("associative_array")
     */
    private $associativeArray;

    /**
     * @var \stdClass
     * @SerializedName("empty_std_class")
     */
    private $emptyStdClass;

    /**
     * @var \stdClass|null
     * @SerializedName("std_class")
     */
    private $stdClass;

    /**
     * @var Entity1|null
     * @SerializedName("entity")
     */
    private $entity;

    /**
     * Entity1 constructor.
     * @param array|null $array
     * @param array|null $associativeArray
     * @param \stdClass|null $stdClass
     * @param Entity1|null $entity
     */
    public function __construct(
        array $array = null,
        array $associativeArray = null,
        \stdClass $stdClass = null,
        Entity1 $entity = null
    ) {
        $this->array = $array;
        $this->associativeArray = $associativeArray;
        $this->emptyStdClass = new \stdClass();
        $this->stdClass = $stdClass;
        $this->entity = $entity;
    }

    /**
     * @param Entity1|null $entity
     * @return Entity1
     */
    public function setEntity($entity)
    {
        $this->entity = $entity;
        return $this;
    }
}