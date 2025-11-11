<?php
declare(strict_types=1);

use gijsbos\Entities\EntityClass;
use gijsbos\Entities\Parsers\EntityClassParser;
use gijsbos\Entities\Parsers\EntityClassPropertyParser;
use PHPUnit\Framework\TestCase;

/**
 * EntityClassPropertyTestClass
 */
class EntityClassPropertyTestClass extends EntityClass
{
    /**
     * @var string $stringProperty
     */
    public $stringProperty;

    /**
     * @var int $intProperty
     */
    public $intProperty;

    /**
     * @var float $floatProperty
     */
    public $floatProperty;

    /**
     * @var double $doubleProperty
     */
    public $doubleProperty;

    /**
     * @var bool $boolProperty
     */
    public $boolProperty;

    /**
     * @var mixed $mixedProperty
     */
    public $mixedProperty;

    /**
     * @var EntityClassPropertyTestClass $singleClass
     */
    public $singleClass;

    /**
     * @var EntityClassPropertyTestClass|EntityClassPropertyTestClassTwo $doubleClass
     */
    public $doubleClass;

    /**
     * @var EntityClassPropertyTestClass[] $singleClassArray
     */
    public $singleClassArray;

    /**
     * @var EntityClassPropertyTestClass[]|EntityClassPropertyTestClassTwo[] $doubleClassArray
     */
    public $doubleClassArray;
}

/**
 * EntityClassPropertyTestClassTwo
 */
class EntityClassPropertyTestClassTwo extends EntityClass
{
    /**
     * @var string $property
     */
    public $property;
}

/**
 * EntityClassPropertyParserTest
 */
final class EntityClassPropertyParserTest extends TestCase 
{
    public function testStringProperty()
    {
        $class = EntityClassParser::parse(EntityClassPropertyTestClass::class);

        $property = $class->getEntityClassProperty("stringProperty");

        $value = "hello world";

        $result = EntityClassPropertyParser::parse($property, $value, true, true);

        $this->assertEquals("hello world", $result);
    }

    public function testIntProperty()
    {
        $class = EntityClassParser::parse(EntityClassPropertyTestClass::class);

        $property = $class->getEntityClassProperty("intProperty");

        $value = "1";

        $result = EntityClassPropertyParser::parse($property, $value, true, true);

        $this->assertEquals(1, $result);
    }

    public function testFloatProperty()
    {
        $class = EntityClassParser::parse(EntityClassPropertyTestClass::class);

        $property = $class->getEntityClassProperty("floatProperty");

        $value = "1.1";

        $result = EntityClassPropertyParser::parse($property, $value, true, true);

        $this->assertEquals(1.1, $result);
    }

    public function testDoubleProperty()
    {
        $class = EntityClassParser::parse(EntityClassPropertyTestClass::class);

        $property = $class->getEntityClassProperty("doubleProperty");

        $value = "1.1";

        $result = EntityClassPropertyParser::parse($property, $value, true, true);

        $this->assertEquals(1.1, $result);
    }

    public function testBoolProperty()
    {
        $class = EntityClassParser::parse(EntityClassPropertyTestClass::class);

        $property = $class->getEntityClassProperty("boolProperty");

        $value = "false";

        $result = EntityClassPropertyParser::parse($property, $value, true, true);

        $this->assertEquals(false, $result);
    }

    public function testMixedPropertyIsInt()
    {
        $class = EntityClassParser::parse(EntityClassPropertyTestClass::class);

        $property = $class->getEntityClassProperty("mixedProperty");

        $value = "1";

        $result = EntityClassPropertyParser::parse($property, $value, true, true);

        $this->assertEquals(1, $result);
    }

    public function testMixedPropertyIsFloat()
    {
        $class = EntityClassParser::parse(EntityClassPropertyTestClass::class);

        $property = $class->getEntityClassProperty("mixedProperty");

        $value = "1.1";

        $result = EntityClassPropertyParser::parse($property, $value, true, true);

        $this->assertEquals(1.1, $result);
    }

    public function testMixedPropertyBoolIsString()
    {
        $class = EntityClassParser::parse(EntityClassPropertyTestClass::class);

        $property = $class->getEntityClassProperty("mixedProperty");

        $value = "false";

        $result = EntityClassPropertyParser::parse($property, $value, true, true);

        $this->assertEquals("false", $result);
    }

    public function EntityClassPropertyTestClassProperty()
    {
        $class = EntityClassParser::parse(EntityClassPropertyTestClass::class);

        $property = $class->getEntityClassProperty("boolProperty");

        $value = "false";

        $result = EntityClassPropertyParser::parse($property, $value, true, true);

        $this->assertEquals(false, $result);
    }

    public function testSingleClass()
    {
        $class = EntityClassParser::parse(EntityClassPropertyTestClass::class);

        $property = $class->getEntityClassProperty("singleClass");

        $value = [
            "stringProperty" => "hi",
            "intProperty" => 1,
        ];

        $result = EntityClassPropertyParser::parse($property, $value, true, true);

        $this->assertInstanceOf(EntityClassPropertyTestClass::class, $result);
    }

    public function testDoubleClassEntityClassPropertyTestClass()
    {
        $class = EntityClassParser::parse(EntityClassPropertyTestClass::class);

        $property = $class->getEntityClassProperty("doubleClass");

        $value = [
            "stringProperty" => "hi",
            "intProperty" => 1,
        ];

        $result = EntityClassPropertyParser::parse($property, $value, true, true);

        $this->assertInstanceOf(EntityClassPropertyTestClass::class, $result);
    }

    public function testDoubleClassEntityClassPropertyTestClassTwo()
    {
        $class = EntityClassParser::parse(EntityClassPropertyTestClass::class);

        $property = $class->getEntityClassProperty("doubleClass");

        $value = [
            "property" => "hi",
        ];

        $result = EntityClassPropertyParser::parse($property, $value, true, true);

        $this->assertInstanceOf(EntityClassPropertyTestClassTwo::class, $result);
    }

    public function testSingleClassArray()
    {
        $class = EntityClassParser::parse(EntityClassPropertyTestClass::class);

        $property = $class->getEntityClassProperty("singleClassArray");

        $value = [
            [
                "stringProperty" => "class1",
            ],
            [
                "stringProperty" => "class2",
            ],
        ];

        $result = EntityClassPropertyParser::parse($property, $value, true, true);

        $this->assertEquals("class1", $result[0]->stringProperty);
        $this->assertEquals("class2", $result[1]->stringProperty);
    }

    public function testDoubleClassArray()
    {
        $class = EntityClassParser::parse(EntityClassPropertyTestClass::class);

        $property = $class->getEntityClassProperty("singleClassArray");

        $value = [
            [
                "stringProperty" => "class1",
            ],
            [
                "property" => "class2",
            ],
        ];

        $result = EntityClassPropertyParser::parse($property, $value, true, true);

        $this->assertInstanceOf(EntityClassPropertyTestClass::class, $result[0]);
        $this->assertEquals("class1", $result[0]->stringProperty);

        $this->assertInstanceOf(EntityClassPropertyTestClass::class, $result[1]);
        $this->assertEquals("class2", $result[1]->property);
    }
}