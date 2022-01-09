<?php

namespace JWorman\Serializer\Tests\Unit\Entities;

use JWorman\Serializer\Attribute\SerializedName;
use JWorman\Serializer\Attribute\Type;

class Entity3
{
    #[SerializedName('string')]
    #[Type('string')]
    private string $string = 'fuzzbizz';

    public function __construct(
        #[SerializedName('entities')]
        #[Type('array<'.Entity2::class.'>')] private readonly array $entities
    ) {
    }
}
