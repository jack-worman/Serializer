<?php

namespace JWorman\Serializer\Tests\Unit;

use JWorman\Serializer\Serializer;
use JWorman\Serializer\Tests\Unit\Entities\Entity1;
use PHPUnit\Framework\TestCase;
use Spatie\Snapshots\MatchesSnapshots;

class SerializerTest extends TestCase
{
    use MatchesSnapshots;

    /**
     * @covers \JWorman\Serializer\Serializer::serialize
     */
    public function testSerializer(): void
    {
        $innerArray = $this->createArray();
        $innerAssociativeArray = $this->createAssociativeArray();
        $innerStdClass = $this->createStdClass();
        $innerEntity = new Entity1();

        $middleArray = $this->createArray($innerArray, $innerAssociativeArray, $innerStdClass, $innerEntity);
        $middleAssociativeArray = $this->createAssociativeArray(
            $innerArray,
            $innerAssociativeArray,
            $innerStdClass,
            $innerEntity
        );
        $middleStdClass = $this->createStdClass($innerArray, $innerAssociativeArray, $innerStdClass, $innerEntity);
        $middleEntity = new Entity1($innerArray, $innerAssociativeArray, $innerStdClass, $innerEntity);

        $entity1 = new Entity1($middleArray, $middleAssociativeArray, $middleStdClass, $middleEntity);

        $serializedEntity = Serializer::serialize($entity1, Serializer::FORMAT_JSON, 3);
        // assertMatchesJsonSnapshot() incorrectly converts empty objects, {}, to empty arrays, [].
        $this->assertMatchesSnapshot($serializedEntity);
    }

    private function createStdClass(
        array $array = null,
        array $associativeArray = null,
        \stdClass $stdClass = null,
        Entity1 $entity = null
    ): \stdClass {
        $createdStdClass = new \stdClass();
        $createdStdClass->null = null;
        $createdStdClass->bool = true;
        $createdStdClass->int = 42;
        $createdStdClass->float = 3.14;
        $createdStdClass->string = 'fizzbuzz';
        $createdStdClass->empty_array = [];
        $createdStdClass->array = $array;
        $createdStdClass->associative_array = $associativeArray;
        $createdStdClass->empty_std_class = new \stdClass();
        $createdStdClass->std_class = $stdClass;
        $createdStdClass->entity = $entity;

        return $createdStdClass;
    }

    private function createArray(
        array $array = null,
        array $associativeArray = null,
        \stdClass $stdClass = null,
        Entity1 $entity = null
    ): array {
        return [
            null,
            true,
            42,
            3.14,
            'fizzbuzz',
            [],
            $array,
            $associativeArray,
            new \stdClass(),
            $stdClass,
            $entity,
        ];
    }

    private function createAssociativeArray(
        array $array = null,
        array $associativeArray = null,
        \stdClass $stdClass = null,
        Entity1 $entity = null
    ): array {
        return [
            'null' => null,
            'bool' => false,
            'int' => 42,
            'float' => 3.14,
            'string' => 'fizzbuzz',
            'empty_array' => [],
            'array' => $array,
            'associative_array' => $associativeArray,
            'empty_std_class' => new \stdClass(),
            'std_class' => $stdClass,
            'entity' => $entity,
        ];
    }

    /**
     * @covers \JWorman\Serializer\Serializer::serialize
     */
    public function testSerializeRecursionLimit(): void
    {
        $entity1 = new Entity1();
        $entity1->setEntity($entity1);
        $this->expectException(\RuntimeException::class);
        Serializer::serialize($entity1, Serializer::FORMAT_JSON, 64);
    }

    /**
     * @covers \JWorman\Serializer\Serializer::deserialize
     */
    public function testDeserialize(): void
    {
        $json = \file_get_contents(__DIR__.'/__snapshots__/SerializerTest__testSerializer__1.txt');
        $entity1 = Serializer::deserialize($json, Entity1::class);
        $this->assertSame(Entity1::class, $entity1::class);
        // \json_decode()'ing because the order of properties is not guaranteed.
        $this->assertEquals(
            \json_decode($json, null, 512, JSON_THROW_ON_ERROR),
            \json_decode(Serializer::serialize($entity1), null, 512, JSON_THROW_ON_ERROR)
        );
    }
}
