<?php

namespace JWorman\Serializer\Tests\Unit\Entities;

use JWorman\Serializer\Annotations as Serializer;

class Entity3
{
    const CLASS_NAME = __CLASS__;

    /**
     * @var Entity2[]
     * @Serializer\SerializedName("entities")
     * @Serializer\Type("array<JWorman\\Serializer\\Tests\\Unit\\Entities\\Entity2>")
     */
    private $entities;

    /**
     * @var string
     * @Serializer\SerializedName("string")
     * @Serializer\Type("string")
     */
    private $string = 'fuzzbizz';

    public function __construct(array $entities)
    {
        $this->entities = $entities;
    }
}
