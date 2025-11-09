<?php
declare(strict_types=1);

namespace gijsbos\Entities;

use Exception;
use gijsbos\ExtFuncs\Utils\DocPropertyParser;
use ReflectionClass;
use ReflectionProperty;

/**
 * EntityClassProperty
 */
class EntityClassProperty extends ReflectionProperty
{
    const BASIC_TYPES = ["string","bool","int","float","double"];

    // Filters array types e.g. string[], DateTime[]
    const FILTER_ARRAY_TYPE = 1;

    // Filters array types e.g. DateTime, DateTime[]
    const FILTER_CLASS_TYPE = 2;

    // Filters array types e.g. DateTime[]
    const FILTER_CLASS_ARRAY_TYPE = 3;

    // Filters classes that extend the EntityClass object
    const FILTER_ENTITY_CLASS_TYPE = 4;

    // Filters classes that extend the EntityClass object and which are defined as array
    const FILTER_ENTITY_CLASS_ARRAY_TYPE = 5;

    /**
     * @var callable[]
     * 
     * Calls callable
     *  callable((string) key, (string) value, (object) entityClassProperty)
     */
    public static $customDocCommentPropertyParsers = [];

    /**
     * @var array $docProperties
     */
    private $docProperties;

    /**
     * @var EntityClassType[] $types
     */
    private $types;

    /**
     * __construct
     */
    public function __construct($classOrObject, $property, bool $init = true)
    {
        parent::__construct($classOrObject, $property);

        // Set params
        $this->docProperties = [];

        // Set varTypes
        $this->types = [];

        // Init
        if($init)
            $this->init();
    }

    /**
     * getEntityClassReflection
     * 
     * @return object
     */
    public function getEntityClassReflection()
    {
        return EntityClassReflection::getEntityClassReflection($this->class);
    }

    /**
     * addCustomPropertyParser
     */
    public static function addCustomPropertyParser(string $key, callable $parser)
    {
        self::$customDocCommentPropertyParsers[$key] = $parser;
    }

    /**
     * setDocProperties
     */
    public function setDocProperties(array $docProperties)
    {
        $this->docProperties = $docProperties;
    }

    /**
     * getDocProperties
     */
    public function getDocProperties()
    {
        return $this->docProperties;
    }

    /**
     * hasDocProperty
     */
    public function hasDocProperty(string $key) : bool
    {
        return array_key_exists($key, $this->docProperties);
    }

    /**
     * getDocProperty
     * 
     * @return null|string
     */
    public function getDocProperty(string $key)
    {
        if($this->hasDocProperty($key))
            return $this->docProperties[$key];

        return null;
    }

    /**
     * setTypes
     */
    public function setTypes(array $types) : void
    {
        $this->types = $types;
    }

    /**
     * getTypes
     */
    public function getTypes($filter = null) : array
    {
        if($filter === null)
            return $this->types;

        // Use filters
        return array_filter($this->types, function($type) use ($filter)
        {
            switch($filter)
            {
                case self::FILTER_ARRAY_TYPE:
                    return $type->isArray();
                case self::FILTER_CLASS_TYPE:
                    return class_exists($type->getName());
                case self::FILTER_CLASS_ARRAY_TYPE:
                    return class_exists($type->getName()) && $type->isArray();
                case self::FILTER_ENTITY_CLASS_TYPE:
                    return class_exists($type->getName()) && (new ReflectionClass($type->getName()))->isSubclassOf(EntityClass::class);
                case self::FILTER_ENTITY_CLASS_ARRAY_TYPE:
                    return class_exists($type->getName()) && $type->isArray() && (new ReflectionClass($type->getName()))->isSubclassOf(EntityClass::class);
                default:
                    return true;
            }
        });
    }

    /**
     * addType
     */
    public function addType(EntityClassType $type) : void
    {
        $this->types[$type->getName()] = $type;
    }

    /**
     * getTypeByName
     * 
     * @return false|EntityClassType
     */
    public function getTypeByName(string $name)
    {
        foreach($this->types as $type)
        {
            if($type->getName() === $name)
                return $type;
        }

        return false;
    }

    /**
     * getClassTypes
     * 
     * @return EntityClassType[]
     */
    public function getClassTypes() : array
    {
        return $this->getTypes(self::FILTER_CLASS_TYPE);
    }

    /**
     * hasClassType
     */
    public function hasClassType() : bool
    {
        return count($this->getClassTypes()) > 0;
    }

    /**
     * hasEntityClassType
     */
    public function hasEntityClassType() : bool
    {
        return count($this->getTypes(self::FILTER_ENTITY_CLASS_TYPE)) > 0;
    }

    /**
     * hasSingleEntityClassType
     *  Property has a single entity class type without a UnionType
     */
    public function hasSingleEntityClassType()
    {
        return count($this->getTypes(self::FILTER_ENTITY_CLASS_TYPE)) == 1 && count($this->types) == 1;
    }

    /**
     * hasUnionEntityClassType
     *  Property has a union operator that contains more than one entity class
     */
    public function hasUnionEntityClassType()
    {
        return count($this->getTypes(self::FILTER_ENTITY_CLASS_TYPE)) > 1;
    }

    /**
     * init
     */
    public function init()
    {
        // Add default parser for 'var' properties
        self::addCustomPropertyParser("var", [$this, 'parseVarDocCommentProperty']);

        // Parse doc ocmment
        $this->docProperties = DocPropertyParser::parse($this, self::$customDocCommentPropertyParsers);
    }

    /**
     * parseType
     * 
     * @param array $types - Used to check whether the current type should throw an exception when not resolved
     */
    private static function parseType(ReflectionProperty $property, string $type, array $types)
    {
        // Set type
        $type = str_ends_with($type, "[]") ? substr($type, 0, strlen($type) - 2) : $type;

        // Default types
        if(in_array($type, array_merge(self::BASIC_TYPES, ["array","mixed","object"])))
            return $type;

        // Get declaring class namespace
        $namespace = $property->getDeclaringClass()->getNamespaceName();

        // Parent class is defined with namespace
        if(strlen($namespace))
        {
            // Type has a slash, which might indicate a literal class not using the declaring namespace of the owning class
            if(strpos($type, "\\") !== false)
            {
                // Check class literally
                if(class_exists($type))
                {
                    return $type;
                }
            }
            else
            {
                // Add namespace and check again
                $className = sprintf("%s\%s", $namespace, $type);

                // Check class with namespace
                if(class_exists($className))
                {
                    return $className;
                }
            }
        }
        
        // Check class literally
        if(class_exists($type))
        {
            return $type;
        }

        // If the mixed type has been included, unresolved classess will not throw an exception
        if(!in_array("mixed", $types))
        {
            $parentClass = $property->class;
            $propertyName = $property->getName();

            // Set exception for when class cannot be resolved
            throw new Exception("Could not resolve property type '$type' for property '{$parentClass}->{$propertyName}'");
        }
        
        // Mixed
        return "mixed";
    }

    /**
     * parseTypes
     */
    private static function parseTypes(ReflectionProperty $property, $types)
    {
        if(is_string($types))
            $types = explode("|", $types);

        return array_map(function($declaredType) use ($property, $types)
        {
            // If var is defined as '@var $varName' without type, we remove the dollar sign
            $declaredType = $declaredType[0] === "$" ? substr($declaredType, 1) : $declaredType;

            // Compare with property name
            if($declaredType === $property->getName())
                return new EntityClassType("mixed", "mixed");

            // Parse type
            $type = self::parseType($property, $declaredType, $types);

            // Return type
            return new EntityClassType($declaredType, $type);
        }, $types);
    }

    /**
     * parseVarDocCommentProperty
     */
    public static function parseVarDocCommentProperty(string $key, string $values, EntityClassProperty $entityClassProperty)
    {
        // Explode
        $types = explode(" ", $values);

        // Check types is set
        if(count($types) === 0)
            return $values;

        // Parse types
        $types = self::parseTypes($entityClassProperty, $types[0]);

        // Set types
        $entityClassProperty->setTypes($types);

        // Return
        return $types;
    }

    /**
     * serialize
     */
    public function serialize()
    {
        // Store data for serialization
        $serialize = [
            "entityClassPropertyClass" => get_called_class(),
            "className" => $this->class,
            "propertyName" => $this->getName(),
            "data" => []
        ];

        // Get reflection class
        $reflection = new ReflectionClass(__CLASS__);

        // Get properties
        $properties = $reflection->getProperties();

        // Add data properties
        foreach($properties as $property)
        {
            $name = $property->getName();

            if(!$property->isStatic() && !$property->isReadOnly() && !in_array($name, ["name","class"]))
            {
                $serialize["data"][$name] = $this->$name;
            }
        }

        return serialize($serialize);
    }

    /**
     * createFromArgs
     */
    public static function unserialize(string $unserialize) : self
    {
        $unserialize = unserialize($unserialize);
        
        // Get unserialize data
        $entityClassPropertyClass = $unserialize["entityClassPropertyClass"];
        $className = $unserialize["className"];
        $propertyName = $unserialize["propertyName"];
        $data = $unserialize["data"];
        
        // Create new EntityClassPropertyInstance
        $instance = new $entityClassPropertyClass($className, $propertyName, false);
        
        // Create reflection
        $reflection = new ReflectionClass(__CLASS__);

        // Add data properties
        foreach($reflection->getProperties() as $property)
        {
            $name = $property->getName();

            if(array_key_exists($name, $data))
            {
                $value = $data[$name];

                $instance->$name = $value;
            }
        }

        //
        return $instance;
    }
}