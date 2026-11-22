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
 * EntityClassPropertyUnionFallbackTestClass
 *  Union types with a class that only exists in some applications, e.g. an entity that is shared between services
 *  while one of its property classes only exists in one of them
 */
class EntityClassPropertyUnionFallbackTestClass extends EntityClass
{
    /**
     * @var \EntityClassPropertyTestClassTwo|array $existingClassOrArray
     */
    public $existingClassOrArray;

    /**
     * @var \Does\Not\Exist\Service|array $missingClassOrArray
     */
    public $missingClassOrArray;

    /**
     * @var \Does\Not\Exist\Service[]|array $missingClassArrayOrArray
     */
    public $missingClassArrayOrArray;

    /**
     * @var \Does\Not\Exist\Service|string $missingClassOrString
     */
    public $missingClassOrString;

    /**
     * @var \Does\Not\Exist\Service|mixed $missingClassOrMixed
     */
    public $missingClassOrMixed;
}

/**
 * EntityClassPropertyMissingClassTestClass
 */
class EntityClassPropertyMissingClassTestClass extends EntityClass
{
    /**
     * @var \Does\Not\Exist\Service $missingClass
     */
    public $missingClass;
}

/**
 * EntityClassPropertyMissingClassesTestClass
 */
class EntityClassPropertyMissingClassesTestClass extends EntityClass
{
    /**
     * @var \Does\Not\Exist\Service|\Does\Not\Exist\Either $missingClasses
     */
    public $missingClasses;
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

        $property = $class->getEntityClassProperty("doubleClassArray");

        $value = [
            [
                "stringProperty" => "class1",
            ],
            [
                "property" => "class2",
            ],
        ];

        $result = EntityClassPropertyParser::parse($property, $value, true, true);

        // Every item is cast into the class that matches it best
        $this->assertInstanceOf(EntityClassPropertyTestClass::class, $result[0]);
        $this->assertEquals("class1", $result[0]->stringProperty);

        $this->assertInstanceOf(EntityClassPropertyTestClassTwo::class, $result[1]);
        $this->assertEquals("class2", $result[1]->property);
    }

    public function testDoubleClassArrayWithoutUnknownProperties()
    {
        $class = EntityClassParser::parse(EntityClassPropertyTestClass::class);

        $property = $class->getEntityClassProperty("doubleClassArray");

        $value = [
            [
                "stringProperty" => "class1",
            ],
            [
                "property" => "class2",
            ],
        ];

        // Without setting unknown properties no data is lost, since every item is cast into its own class
        $result = EntityClassPropertyParser::parse($property, $value, false, true);

        $this->assertInstanceOf(EntityClassPropertyTestClass::class, $result[0]);
        $this->assertEquals("class1", $result[0]->stringProperty);

        $this->assertInstanceOf(EntityClassPropertyTestClassTwo::class, $result[1]);
        $this->assertEquals("class2", $result[1]->property);
    }

    public function testDoubleClassArrayEmptyItem()
    {
        $class = EntityClassParser::parse(EntityClassPropertyTestClass::class);

        $property = $class->getEntityClassProperty("doubleClassArray");

        $result = EntityClassPropertyParser::parse($property, [[], ["property" => "class2"]], true, true);

        // An empty item matches no class better than another, the first class is used
        $this->assertInstanceOf(EntityClassPropertyTestClass::class, $result[0]);
        $this->assertInstanceOf(EntityClassPropertyTestClassTwo::class, $result[1]);
    }

    public function testUnionExistingClassOrArrayIsCastToClass()
    {
        $class = EntityClassParser::parse(EntityClassPropertyUnionFallbackTestClass::class);

        $property = $class->getEntityClassProperty("existingClassOrArray");

        $result = EntityClassPropertyParser::parse($property, ["property" => "hi"], true, true);

        $this->assertInstanceOf(EntityClassPropertyTestClassTwo::class, $result);
        $this->assertEquals("hi", $result->property);
    }

    public function testUnionMissingClassOrArrayFallsBackToArray()
    {
        $class = EntityClassParser::parse(EntityClassPropertyUnionFallbackTestClass::class);

        $property = $class->getEntityClassProperty("missingClassOrArray");

        $this->assertEquals(["array"], array_map(fn($type) => $type->getName(), $property->getTypes()));

        $value = ["id" => "svc_1", "name" => "Files"];

        $result = EntityClassPropertyParser::parse($property, $value, true, true);

        $this->assertSame($value, $result);
    }

    public function testUnionMissingClassArrayOrArrayFallsBackToArray()
    {
        $class = EntityClassParser::parse(EntityClassPropertyUnionFallbackTestClass::class);

        $property = $class->getEntityClassProperty("missingClassArrayOrArray");

        $value = [["id" => "svc_1"], ["id" => "svc_2"]];

        $result = EntityClassPropertyParser::parse($property, $value, true, true);

        $this->assertSame($value, $result);
    }

    public function testUnionMissingClassOrStringFallsBackToString()
    {
        $class = EntityClassParser::parse(EntityClassPropertyUnionFallbackTestClass::class);

        $property = $class->getEntityClassProperty("missingClassOrString");

        $result = EntityClassPropertyParser::parse($property, "svc_1", true, true);

        $this->assertSame("svc_1", $result);
    }

    public function testUnionMissingClassOrMixedIsUnchanged()
    {
        $class = EntityClassParser::parse(EntityClassPropertyUnionFallbackTestClass::class);

        $property = $class->getEntityClassProperty("missingClassOrMixed");

        // The unresolved class becomes 'mixed', as before
        $this->assertEquals(["mixed", "mixed"], array_map(fn($type) => $type->getName(), $property->getTypes()));

        $result = EntityClassPropertyParser::parse($property, "1", true, true);

        $this->assertSame(1, $result);
    }

    public function testUnionMissingClassFallbackCreateFromArgs()
    {
        $entity = EntityClassPropertyUnionFallbackTestClass::createFromArgs([
            "existingClassOrArray" => ["property" => "hi"],
            "missingClassOrArray" => ["id" => "svc_1"],
        ]);

        $this->assertInstanceOf(EntityClassPropertyTestClassTwo::class, $entity->existingClassOrArray);
        $this->assertSame(["id" => "svc_1"], $entity->missingClassOrArray);
    }

    public function testMissingClassThrows()
    {
        $this->expectExceptionMessage("Could not resolve property type '\Does\Not\Exist\Service' for property 'EntityClassPropertyMissingClassTestClass->missingClass'");

        EntityClassParser::parse(EntityClassPropertyMissingClassTestClass::class);
    }

    public function testUnionOfMissingClassesThrows()
    {
        $this->expectExceptionMessage("Could not resolve property type '\Does\Not\Exist\Service' for property 'EntityClassPropertyMissingClassesTestClass->missingClasses'");

        EntityClassParser::parse(EntityClassPropertyMissingClassesTestClass::class);
    }
}