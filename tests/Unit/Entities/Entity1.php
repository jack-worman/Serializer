<?php

namespace JWorman\Serializer\Tests\Unit\Entities;

use JWorman\Serializer\Attribute\SerializedName;
use JWorman\Serializer\Attribute\Type;

class Entity1
{
    final public const CLASS_NAME = self::class;

    private readonly mixed $propertyWithNoAnnotations;

    #[SerializedName('null')]
    #[Type('null')]
    private mixed $null = null;

    #[SerializedName('bool')]
    #[Type('bool')]
    private bool $bool = false;

    #[SerializedName('int')]
    #[Type('int')]
    private int $int = 42;

    #[SerializedName('float')]
    #[Type('float')]
    private float $float = 3.14;

    #[SerializedName('string')]
    #[Type('string')]
    private string $string = 'fizzbuzz';

    #[SerializedName('empty_array')]
    #[Type('array')]
    private array $emptyArray = [];

    #[SerializedName('empty_std_class')]
    #[Type('object')]
    private readonly \stdClass $emptyStdClass;

    public function __construct(
        #[SerializedName('array')] #[Type('array')] private readonly ?array $array = null,
        #[SerializedName('associative_array')] #[Type('array')] private readonly ?array $associativeArray = null,
        #[SerializedName('std_class')] #[Type('object')] private readonly ?\stdClass $stdClass = null,
        #[SerializedName('entity')] #[Type(self::class)] private ?Entity1 $entity = null
    ) {
        $this->emptyStdClass = new \stdClass();
    }

    public function setEntity(self $entity): self
    {
        $this->entity = $entity;

        return $this;
    }
}
