<?php

/**
 * Entity2
 * @copyright HealthCall, LLC
 */

namespace JWorman\Serializer\Tests\Unit\Entities;

use JWorman\Serializer\Annotations as Serializer;

class Entity2
{
    const CLASS_NAME = __CLASS__;

    /**
     * @var string
     * @Serializer\SerializedName("string")
     * @Serializer\Type("string")
     */
    private $string = 'fuzzbizz';
}
