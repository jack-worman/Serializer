<?php

namespace JWorman\Serializer\Tests\Unit;

use JWorman\Serializer\Serializer;
use JWorman\Serializer\Tests\Unit\Entities\Entity2;
use JWorman\Serializer\Tests\Unit\Entities\Entity3;
use PHPUnit\Framework\TestCase;

class ArrayTypeSerializerTest extends TestCase
{
    public function testArrayType(): void
    {
        $entity2 = new Entity2();
        $entity3 = new Entity3([$entity2, $entity2]);
        $serializedEntity = Serializer::serialize($entity3, Serializer::FORMAT_JSON, 10);
        self::assertSame(
            '{"string":"fuzzbizz","entities":[{"string":"fuzzbizz"},{"string":"fuzzbizz"}]}',
            $serializedEntity
        );
        $deserializedEntity = Serializer::deserialize($serializedEntity, Entity3::class);
        self::assertEquals($entity3, $deserializedEntity);
    }
}
