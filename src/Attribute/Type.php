<?php

declare(strict_types=1);

namespace JWorman\Serializer\Attribute;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class Type
{
    public function __construct(public string $type)
    {
    }
}
