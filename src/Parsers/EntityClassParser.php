<?php
declare(strict_types=1);

namespace gijsbos\Entities\Parsers;

use gijsbos\Entities\EntityClassReflection;
use gijsbos\ExtFuncs\Utils\DocPropertyParser;

/**
 * EntityClassParser
 */
class EntityClassParser
{
    /**
     * @var callable[]
     * 
     * Calls callable
     *  callable((string) key, (string) value, (object) entityClassProperty)
     */
    public static $customDocCommentPropertyParsers = [];

    /**
     * __construct
     */
    public function __construct()
    {
        
    }

    /**
     * addCustomPropertyParser
     */
    public static function addCustomPropertyParser(string $key, callable $parser)
    {
        self::$customDocCommentPropertyParsers[$key] = $parser;
    }

    /**
     * parseClass
     */
    public function parseClass(string $className)
    {
        // Use called class to allow EntityClassReflection extensions
        $entityClassReflectionClassName = $className::getEntityClassReflectionClassName();

        // Create entityClassReflection
        $entityClassReflection = new $entityClassReflectionClassName($className);

        // Parse class properties
        $entityClassReflection->docProperties = DocPropertyParser::parse($entityClassReflection, self::$customDocCommentPropertyParsers);

        // Get entity class property class name
        $entityClassPropertyClassName = $className::getEntityClassPropertyClassName();

        // Set properties
        foreach($entityClassReflection->getProperties() as $property)
        {
            // Parse property
            $property = new $entityClassPropertyClassName($className, $property->getName());

            // Add property
            $entityClassReflection->addEntityClassProperty($property);
        }

        // Return 
        return $entityClassReflection;
    }

    /**
     * parse
     */
    public static function parse(string $className, bool $verbose = false) : EntityClassReflection
    {
        return (new self($verbose))->parseClass($className);
    }
}