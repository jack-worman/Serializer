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
        $innerArray = $this->createArray(null, null, null, null);
        $innerAssociativeArray = $this->createAssociativeArray(null, null, null, null);
        $innerStdClass = $this->createStdClass(null, null, null, null);
        $innerEntity = new Entity1(null, null, null, null);

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

        $serializedEntity = Serializer::serialize($entity1);
        /**
         * There is a bug that converts empty objects to [] instead of {}. The serializer works correctly, the snapshot
         * test is the one that's wrong. So, I had to manually change the snapshot.
         */
        $this->assertMatchesJsonSnapshot($serializedEntity);
    }

    /**
     * @param $array
     * @param $associativeArray
     * @param $stdClass
     * @param $entity
     * @return \stdClass
     */
    private function createStdClass($array, $associativeArray, $stdClass, $entity)
    {
        $createdStdClass = new \stdClass();
        $createdStdClass->null = null;
        $createdStdClass->bool = true;
        $createdStdClass->int = 42;
        $createdStdClass->float = 3.14159265358979;
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
     * @param $array
     * @param $associativeArray
     * @param $stdClass
     * @param $entity
     * @return array
     */
    private function createArray($array, $associativeArray, $stdClass, $entity)
    {
        return array(
            null,
            true,
            42,
            3.14159265358979,
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
     * @param $array
     * @param $associativeArray
     * @param $stdClass
     * @param $entity
     * @return array
     */
    private function createAssociativeArray($array, $associativeArray, $stdClass, $entity)
    {
        return array(
            'null' => null,
            'bool' => false,
            'int' => 42,
            'float' => 3.14159265358979,
            'string' => 'fizzbuzz',
            'empty_array' => array(),
            'array' => $array,
            'associative_array' => $associativeArray,
            'empty_std_class' => new \stdClass(),
            'std_class' => $stdClass,
            'entity' => $entity
        );
    }
}