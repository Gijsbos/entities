<?php
declare(strict_types=1);

use gijsbos\Entities\EntityClass;
use gijsbos\Entities\EntityClassReflection;
use PHPUnit\Framework\TestCase;

/**
 * TestEntityClass
 * 
 * @Docs This is a test entity class 
 * @multiple value1
 * @multiple value2
 */
class TestEntityClass extends EntityClass
{
    /**
     * @var string $name
     * 
     * @regexp "/\w+/"
     */
    public $name;

    /**
     * @var int $age
     */
    public $age;

    /**
     * @var $noType
     */
    public $noType;

    /**
     * @var DateTime $created
     */
    public $created;

    /**
     * @var TestAddressEntityClass $address
     */
    public $address;

    /**
     * @var TestAddressEntityClass[] $addresses
     */
    public $addresses;

    /**
     * @var TestEntityClass|TestAddressEntityClass
     */
    public $subject1;

    /**
     * @var TestEntityClass|TestAddressEntityClass
     */
    public $subject2;
}

/**
 * TestAddressEntityClass
 */
class TestAddressEntityClass extends EntityClass
{
    /**
     * @var string $country
     */
    public $country;

    /**
     * @var string $region
     */
    public $region;

    /**
     * @var string $city
     */
    public $city;

    /**
     * @var int $number
     */
    public $number;

    /**
     * @var bool $primary
     */
    public $primary;
}

/**
 * EntityClassTest
 */
final class EntityClassTest extends TestCase
{
    public function testCreateFromArgs()
    {
        // Create entity
        $class = TestEntityClass::createFromArgs([
            "name" => "gijs",
            "age" => "1",
            "noType" => "will be mixed",
            "created" => $created = "2023-05-02T11:12:43+00:00",
            "address" => [
                "country" => "Netherlands",
                "region" => "Noord Holland",
                "city" => "Amsterdam",
                "number" => "1",
                "primary" => "true"
            ],
            "addresses" => [
                [
                    "country" => "German",
                    "region" => "Baskenland",
                    "city" => "Frankfurt",
                    "number" => "12",
                    "primary" => "false"
                ],
                [
                    "country" => "Belgium",
                    "region" => "Flanders",
                    "city" => "Oudenaarde",
                    "number" => "123",
                    "primary" => "false"
                ]
            ],
            "subject1" => [
                "name" => "gijs",
                "age" => 1
            ],
            "subject2" => [
                "country" => "Netherlands",
                "region" => "Noord Holland"
            ]
        ]);

        // Verify created entity
        $this->assertInstanceOf(TestEntityClass::class, $class);
        $this->assertIsString($class->name);
        $this->assertIsInt($class->age);

        // Check noType
        $this->assertIsString($class->noType);
        $this->assertInstanceOf(DateTime::class, $class->created);
        $this->assertEquals($created, $class->created->format("c"));
        $this->assertInstanceOf(TestAddressEntityClass::class, $class->address);
        $this->assertIsArray($class->addresses);
        $this->assertIsString($class->address->country);
        $this->assertIsString($class->address->region);
        $this->assertIsString($class->address->city);
        $this->assertIsInt($class->address->number);
        $this->assertIsBool($class->address->primary);
        $this->assertInstanceOf(TestEntityClass::class, $class->subject1);
        $this->assertInstanceOf(TestAddressEntityClass::class, $class->subject2);
    }

    /**
     * @case
     * In case a property is defined as a non array type such as Address (not Address[])
     * but the value receives is a sequential array, we automatically grab the first array value.
     */
    public function testNonArrayTypeReceivesSequenceArray()
    {
        // Create entity
        $class = TestEntityClass::createFromArgs([
            "name" => "gijs",
            "address" => [
                [
                    "country" => "Netherlands",
                    "region" => "Noord Holland",
                    "city" => "Amsterdam",
                    "number" => "1",
                    "primary" => "true"
                ]
            ],
        ]);

        // Verify created entity
        $this->assertInstanceOf(TestAddressEntityClass::class, $class->address);
    }

    /**
     * @case
     * In case a property is defined as an array type such as Address[] (not Address)
     * but the value receives is a non-sequential array, we turn the value into the first item of a sequential array.
     */
    public function testArrayTypeReceivesNonSequenceArray()
    {
        // Create entity
        $class = TestEntityClass::createFromArgs([
            "name" => "gijs",
            "addresses" => [
                "country" => "Netherlands",
                "region" => "Noord Holland",
                "city" => "Amsterdam",
                "number" => "1",
                "primary" => "true"
            ],
        ]);

        // Verify created entity
        $this->assertInstanceOf(TestAddressEntityClass::class, $class->addresses[0]);
    }

    /**
     * @case
     *  Set EntityClass property using an EntityClassProperty from another class
     */
    public function testSetPropertyUsingExternalEntityClassProperty()
    {
        // Create entity
        $class = TestEntityClass::createFromArgs([
            "name" => "gijs",
            "primary" => 1, // <-- property does not exist in TestEntityClass
        ], true, [
            "primary" => TestAddressEntityClass::getEntityClassReflection()->getEntityClassProperty("primary")
        ]);

        // Verify created entity
        $this->assertEquals(true, $class->primary);
    }

    public function testSetPropertyUsingExternalEntityClassPropertyFailure()
    {
        $this->expectExceptionMessage("Could not get entity class property 'primary', property does not exist");
        
        // Create entity
        $class = TestEntityClass::createFromArgs([
            // "name" => "gijs",
            "primary" => 1, // <-- property does not exist in TestEntityClass
        ], false);
    }

    public function testCreateFromArgsBenchmark()
    {
        $start = bench_start();

        for($i = 0; $i < 1000; $i++)
        {
            // Create entity
            $class = TestEntityClass::createFromArgs([
                "name" => "gijs",
                "age" => "1",
                "address" => [
                    "country" => "Netherlands",
                    "region" => "Noord Holland",
                    "city" => "Amsterdam",
                    "number" => "1",
                    "primary" => "true"
                ],
                "addresses" => [
                    [
                        "country" => "German",
                        "region" => "Baskenland",
                        "city" => "Frankfurt",
                        "number" => "12",
                        "primary" => "false"
                    ],
                    [
                        "country" => "Belgium",
                        "region" => "Flanders",
                        "city" => "Oudenaarde",
                        "number" => "123",
                        "primary" => "false"
                    ]
                ],
            ]);
        }

        // Parse
        $time = bench_end($start, "EntityClass::createFromArgs benchmark", false);

        // Around 0.34 on 24 jul 2024
        $this->assertTrue($time<0.4);
    }

    /**
     * Multiple doc comments that use the same key will be turned into an array
     */
    public function testParseDocPropertyArray()
    {
        $testEntityClass = EntityClassReflection::getEntityClassReflection(TestEntityClass::class);
        $result = $testEntityClass->getDocProperty("multiple");
        $this->assertIsArray($result);
    }

    public function testExtractDocProperty()
    {
        $result = TestEntityClass::name("regexp");
        $expectedResult = "/\w+/";
        $this->assertEquals($expectedResult, $result);
    }
}