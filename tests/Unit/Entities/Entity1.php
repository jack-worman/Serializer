<?php

/**
 * Entity1.php
 * @author Jack Worman
 */

namespace JWorman\Serializer\Tests\Unit\Entities;

use JWorman\Serializer\Annotations as Serializer;

/**
 * Class Entity1
 * @package JWorman\Serializer\Tests\Unit\Entities
 */
class Entity1
{
    const CLASS_NAME = __CLASS__;

    /**
     * @var null
     * @Serializer\SerializedName("null")
     * @Serializer\Type("null")
     */
    private $null = null;

    /**
     * @var bool
     * @Serializer\SerializedName("bool")
     * @Serializer\Type("bool")
     */
    private $bool = false;

    /**
     * @var int
     * @Serializer\SerializedName("int")
     * @Serializer\Type("int")
     */
    private $int = 42;

    /**
     * @var float
     * @Serializer\SerializedName("float")
     * @Serializer\Type("float")
     */
    private $float = 3.14;

    /**
     * @var string
     * @Serializer\SerializedName("string")
     * @Serializer\Type("string")
     */
    private $string = 'fizzbuzz';

    /**
     * @var array
     * @Serializer\SerializedName("empty_array")
     * @Serializer\Type("array")
     */
    private $emptyArray = array();

    /**
     * @var array|null
     * @Serializer\SerializedName("array")
     * @Serializer\Type("array")
     */
    private $array;

    /**
     * @var array|null
     * @Serializer\SerializedName("associative_array")
     * @Serializer\Type("array")
     */
    private $associativeArray;

    /**
     * @var \stdClass
     * @Serializer\SerializedName("empty_std_class")
     * @Serializer\Type("object")
     */
    private $emptyStdClass;

    /**
     * @var \stdClass|null
     * @Serializer\SerializedName("std_class")
     * @Serializer\Type("object")
     */
    private $stdClass;

    /**
     * @var Entity1|null
     * @Serializer\SerializedName("entity")
     * @Serializer\Type("JWorman\\Serializer\\Tests\\Unit\\Entities\\Entity1")
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