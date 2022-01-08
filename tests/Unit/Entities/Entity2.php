<?php

namespace JWorman\Serializer\Tests\Unit\Entities;

use JWorman\Serializer\Attribute\SerializedName;
use JWorman\Serializer\Attribute\Type;

class Entity2
{
    #[SerializedName('string')]
    #[Type('string')]
    private string $string = 'fuzzbizz';
}
