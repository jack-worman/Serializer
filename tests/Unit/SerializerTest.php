<?php

/**
 * SerializerTest.php
 * @author Jack Worman
 */

namespace JWorman\Serializer\Tests\Unit;

use JWorman\Serializer\Serializer;
use JWorman\Serializer\Tests\Unit\Entities\Entity1;
use PHPUnit\Framework\TestCase;
use Spatie\Snapshots\MatchesSnapshots;

/**
 * Class SerializerTest
 * @package JWorman\Serializer\Tests\Unit
 */
class SerializerTest extends TestCase
{
    use MatchesSnapshots;

    /**
     * @covers \JWorman\Serializer\Serializer::serialize
     */
    public function testSerializer()
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

        $start = microtime(true);
        for ($i = 0; $i < 20000; $i++) {
            $serializedEntity = Serializer::serialize($entity1, Serializer::ENCODING_TYPE_JSON, 3);
        }
        var_dump(microtime(true) - $start);
        // assertMatchesJsonSnapshot() incorrectly converts empty objects, {}, to empty arrays, [].
        $this->assertMatchesSnapshot($serializedEntity);
    }

    /**
     * @param array|null $array
     * @param array|null $associativeArray
     * @param \stdClass|null $stdClass
     * @param Entity1|null $entity
     * @return \stdClass
     */
    private function createStdClass(
        array $array = null,
        array $associativeArray = null,
        \stdClass $stdClass = null,
        Entity1 $entity = null
    ) {
        $createdStdClass = new \stdClass();
        $createdStdClass->null = null;
        $createdStdClass->bool = true;
        $createdStdClass->int = 42;
        $createdStdClass->float = 3.14;
        $createdStdClass->string = 'fizzbuzz';
        $createdStdClass->empty_array = array();
        $createdStdClass->array = $array;
        $createdStdClass->associative_array = $associativeArray;
        $createdStdClass->empty_std_class = new \stdClass();
        $createdStdClass->std_class = $stdClass;
        $createdStdClass->entity = $entity;
        return $createdStdClass;
    }

    /**
     * @param array|null $array
     * @param array|null $associativeArray
     * @param \stdClass|null $stdClass
     * @param Entity1|null $entity
     * @return array
     */
    private function createArray(
        array $array = null,
        array $associativeArray = null,
        \stdClass $stdClass = null,
        Entity1 $entity = null
    ) {
        return array(
            null,
            true,
            42,
            3.14,
            'fizzbuzz',
            array(),
            $array,
            $associativeArray,
            new \stdClass(),
            $stdClass,
            $entity
        );
    }

    /**
     * @param array|null $array
     * @param array|null $associativeArray
     * @param \stdClass|null $stdClass
     * @param Entity1|null $entity
     * @return array
     */
    private function createAssociativeArray(
        array $array = null,
        array $associativeArray = null,
        \stdClass $stdClass = null,
        Entity1 $entity = null
    ) {
        return array(
            'null' => null,
            'bool' => false,
            'int' => 42,
            'float' => 3.14,
            'string' => 'fizzbuzz',
            'empty_array' => array(),
            'array' => $array,
            'associative_array' => $associativeArray,
            'empty_std_class' => new \stdClass(),
            'std_class' => $stdClass,
            'entity' => $entity
        );
    }

    /**
     * @covers \JWorman\Serializer\Serializer::serialize
     */
    public function testSerializeRecursionLimit()
    {
        $entity1 = new Entity1();
        $entity1->setEntity($entity1);
        $this->expectException(get_class(new \RuntimeException()));
        Serializer::serialize($entity1);
    }
}
