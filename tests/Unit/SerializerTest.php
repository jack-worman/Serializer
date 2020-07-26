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
        $stdClass = new \stdClass();
        $stdClass->prop1 = '1';
        $stdClass->prop2 = array(
            '12323',
            array('1312' => '321312', '123')
        );

        $entity2 = new Entity1();
        $entity2
            ->setNull(null)
            ->setBool(false)
            ->setFloat(3.14159265358979)
            ->setInt(5)
            ->setString('fizzbuzz')
            ->setArray(array(false, 1.5, 5, 'fizzbuzz'))
            ->setAssociativeArray(array('key' => false, 1.5, 5, 'fizzbuzz'))
            ->setStdClass($stdClass)
            ->setEntity(null);

        $entity1 = new Entity1();
        $entity1
            ->setNull(null)
            ->setBool(false)
            ->setFloat(3.14159265358979)
            ->setInt(5)
            ->setString('fizzbuzz')
            ->setArray(array(false, 1.5, 5, 'fizzbuzz'))
            ->setAssociativeArray(array('key' => false, 1.5, 5, 'fizzbuzz'))
            ->setStdClass($stdClass)
            ->setEntity($entity2);



        $serializedEntity = Serializer::serialize($entity1);
        var_dump($serializedEntity);
        $this->assertMatchesJsonSnapshot($serializedEntity);

//        $unserializedEntity = Serializer::deserialize(json_decode($serializedEntity), Entity1::CLASS_NAME);
//        var_dump($unserializedEntity);
    }
}