<?php
declare(strict_types=1);

namespace gijsbos\Entities;

use AllowDynamicProperties;
use InvalidArgumentException;
use ReflectionClass;
use gijsbos\Entities\Exceptions\EntityClassReflectionException;
use gijsbos\Entities\Parsers\EntityClassParser;
use gijsbos\Entities\Utils\EntityClassCache;

/**
 * EntityClassReflection
 */
#[AllowDynamicProperties]
class EntityClassReflection extends ReflectionClass
{
    // Filter constants
    const FILTER_CLASS_PROPERTIES = 1;
    const FILTER_ENTITY_PROPERTIES = 2;
    const FILTER_BASIC_TYPES = 3;

    /**
     * @var string $filePath
     */
    public $filePath;

    /**
     * @var array $docProperties
     */
    public $docProperties;

    /**
     * @var array $entityClassProperties
     */
    public $entityClassProperties;

    /**
     * __construct
     */
    public function __construct(string $objectOrClass)
    {
        parent::__construct($objectOrClass);

        $this->filePath = $this->getFileName();
        $this->docProperties = [];
        $this->entityClassProperties = [];
    }

    /**
     * addEntityClassProperty
     */
    public function addEntityClassProperty(EntityClassProperty $property) : void
    {
        $this->entityClassProperties[$property->getName()] = $property;
    }

    /**
     * setFilePath
     */
    public function setFilePath(string $filePath) : void
    {
        $this->filePath = $filePath;
    }

    /**
     * getFilePath
     */
    public function getFilePath() : string
    {
        return $this->filePath;
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
     * @return false|string|array
     */
    public function getDocProperty(string $key) : false | string | array
    {
        if($this->hasDocProperty($key))
            return $this->docProperties[$key];

        return false;
    }

    /**
     * setEntityClassProperties
     * 
     * @param EntityClassProperty[] $entityClassProperties - Assoc array containing column[key] = EntityClassProperty[value] pairs
     */
    public function setEntityClassProperties(array $entityClassProperties) : void
    {
        $this->entityClassProperties = $entityClassProperties;
    }

    /**
     * getEntityClassProperties
     * 
     * @return EntityClassProperty[] $entityClassProperties - Assoc array containing column[key] = EntityClassProperty[value] pairs
     */
    public function getEntityClassProperties() : array
    {
        return $this->entityClassProperties;
    }

    /**
     * hasEntityClassProperty
     */
    public function hasEntityClassProperty(string $propertyName) : bool
    {
        return array_key_exists($propertyName, $this->entityClassProperties);
    }

    /**
     * getEntityClassProperty
     */
    public function getEntityClassProperty(string $propertyName, bool $throws = true) : false | EntityClassProperty
    {
        if(!array_key_exists($propertyName, $this->entityClassProperties))
        {
            if($throws)
                throw new EntityClassReflectionException("Could not get entity class property '$propertyName', property does not exist");

            return false;
        }

        return $this->entityClassProperties[$propertyName];
    }

    /**
     * updateCache
     */
    public function updateCache()
    {
        (new EntityClassCache())->storeEntityClassReflectionInCache($this);
    }

    /**
     * getEntityClassReflection
     */
    public static function getEntityClassReflection(string $className, bool $useCache = true)
    {
        return (new EntityClassCache())->get($className, $useCache);
    }

    /**
     * parseEntityClassReflection
     */
    public static function parseEntityClassReflection(string $className, bool $updateCache = false)
    {
        $entityClass = EntityClassParser::parse($className);

        if($updateCache)
            $entityClass->updateCache();

        return $entityClass;
    }

    /**
     * serialize
     */
    public function serialize()
    {
        $serialize = [
            "entityClassReflectionClass" => __CLASS__,
            "className" => $this->getName(),
            "data" => [],
        ];

        // Create reflection
        $reflection = new ReflectionClass(__CLASS__);

        // Add data properties
        foreach($reflection->getProperties() as $property)
        {
            $name = $property->getName();

            if(!$property->isStatic() && !$property->isReadOnly() && !in_array($name, ["name","class"]))
            {
                // Set filepath
                if($name === "filePath")
                    $serialize["data"][$name] = $this->getFilePath();

                // Parse entityClassProperties
                else if($name === "entityClassProperties")
                {
                    $serialize["data"][$name] = [];

                    foreach($this->entityClassProperties as $propertyName => $property)
                        $serialize["data"][$name][$propertyName] = $property->serialize();
                }

                // Default
                else
                    $serialize["data"][$name] = $this->$name;
            }
        }

        return serialize($serialize);
    }

    /**
     * unserializeEnityClassProperties
     */
    private static function unserializeEnityClassProperties(array $data)
    {
        $entityClassProperties = [];

        foreach($data as $propertyName => $propertyData)
        {
            $entityClassProperties[$propertyName] = EntityClassProperty::unserialize($propertyData);
        }

        return $entityClassProperties;
    }

    /**
     * unserialize
     */
    public static function unserialize(string $unserialize)
    {
        $unserialize = unserialize($unserialize);
        
        // Get properties
        $entityClassReflectionClass = $unserialize["entityClassReflectionClass"];
        $className = $unserialize["className"];
        $data = $unserialize["data"];

        // Include class
        if(!class_exists($className) && is_string(@$data["filePath"]))
            include_once $data["filePath"];

        // Create new reflection
        $entityClassReflection = new $entityClassReflectionClass($className);

        // Create reflection
        $reflection = new ReflectionClass(__CLASS__);

        // Add data properties
        foreach($reflection->getProperties() as $property)
        {
            $name = $property->getName();

            if(array_key_exists($name, $data))
            {
                $value = $data[$name];

                if($name == "entityClassProperties")
                {
                    $entityClassReflection->$name = self::unserializeEnityClassProperties($value);
                }
                else
                {
                    $entityClassReflection->$name = $value;
                }
            }
        }

        //
        return $entityClassReflection;
    }

    /**
     * parseFilterInput
     */
    private static function parseFilterInput($filter = null, &$filterFlags = null, &$filterProperties = null, &$filterDocProperties = null)
    {
        if($filter === null)
            return null;

        // Set filter flags
        $filterFlags = null;
        
        // Array
        if(is_array($filter))
        {
            // Extract filter flag
            $extractFilterFlag = array_filter($filter, 'is_int');
            if(count($extractFilterFlag))
                $filterFlags = reset($extractFilterFlag);

            // Get filter arrays
            $extractArrayInputs = array_filter($filter, 'is_array');

            // Read array inputs
            foreach($extractArrayInputs as $arrayInput)
            {
                if(array_key_exists("docProperties", $arrayInput))
                {
                    $value = $arrayInput["docProperties"];

                    if(!is_array($value))
                        throw new InvalidArgumentException("Invalid argument for properties filter using value of type '" . get_type($value) . "'");

                    $filterDocProperties = $value;
                }

                else if(array_key_exists("properties", $arrayInput))
                {
                    $value = $arrayInput["properties"];

                    if(!is_array($value))
                        throw new InvalidArgumentException("Invalid argument for properties filter using value of type '" . get_type($value) . "'");

                    $filterProperties = $value;
                }
            }
        }

        // Filter flag only
        else if(is_int($filter))
        {
            $filterFlags = $filter;
        }
    }

    /**
     * getPropertyList
     */
    public static function getPropertyList(string $className, $filter = null)
    {
        $reflection = self::getEntityClassReflection($className);

        // Parse filter input
        self::parseFilterInput($filter, $filterFlags, $filterProperties, $filterDocProperties);

        // Create list
        $list = [];

        // Add properties
        foreach($reflection->entityClassProperties as $property)
        {
            $name = $property->getName();

            // Exclude static
            if(!$property->isStatic())
            {
                // Apply filters
                if(is_int($filterFlags))
                {
                    if($filterFlags & self::FILTER_CLASS_PROPERTIES)
                    {
                        if($property->hasClassType())
                            continue;
                    }

                    if($filterFlags & self::FILTER_ENTITY_PROPERTIES)
                    {
                        if($property->hasEntityClassType())
                            continue;
                    }

                    if($filterFlags & self::FILTER_BASIC_TYPES)
                    {
                        $types = $property->getTypes();

                        if(count($types))
                        {
                            $type = reset($types);

                            if(!in_array($type->getName(), EntityClassProperty::BASIC_TYPES))
                                continue;
                        }
                    }                    
                }

                // Filter doc properties
                if(is_array($filterProperties) && in_array($name, $filterProperties))
                {
                    continue;
                }

                // Filter doc properties
                if(is_array($filterDocProperties))
                {
                    $filterProperty = false;

                    foreach($filterDocProperties as $docProperty)
                    {
                        if($property->hasDocProperty($docProperty))
                        {
                            $filterProperty = true;
                            break;
                        }
                    }

                    if($filterProperty)
                        continue;
                }
    
                //
                $list[$name] = $property;
            }
        }

        // Return list
        return $list;
    }
}