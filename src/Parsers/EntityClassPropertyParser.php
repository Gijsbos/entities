<?php
declare(strict_types=1);

namespace gijsbos\Entities\Parsers;

use DateTime;
use gijsbos\Entities\EntityClass;
use gijsbos\Entities\EntityClassProperty;
use gijsbos\Entities\EntityClassType;
use ReflectionClass;

/**
 * EntityClassPropertyValueParser
 */
class EntityClassPropertyValueParser
{
    /**
     * __construct
     */
    public function __construct()
    {
        
    }

    /**
     * castStringValue
     */
    private function castStringValue($value)
    {
        if(is_object($value))
        {
            if($value instanceof DateTime)
                return $value->format("c");
        }
        else if(is_array($value))
            return $value;
        else
            return strval($value);
    }

    /**
     * castBooleanValue
     */
    private function castBooleanValue($value)
    {
        if(is_string($value))
        {
            switch($value)
            {
                case "true": return true;
                case "1": return true;
                case "false": return false;
                case "0": return false;
                default:

            }
        }
        return $value;
    }

    /**
     * castDateTime
     */
    private function castDateTime(EntityClassProperty $entityClassProperty, $value)
    {
        // Convert strings
        if(is_string($value))
        {
            $time = strtotime($value);
            
            // Failed
            if($time === false)
                return $value;
    
            // Check if format has been provided
            if($entityClassProperty->hasDocProperty("format"))
            {
                $format = $entityClassProperty->getDocProperty("format");
    
                // Return ISO8601 format
                if($format == "ISO8601" || $format == "c")
                    return date('c', $time);
    
                // Return format string
                return (new DateTime())->setTimestamp($time)->format($format);
            }
    
            // Return object
            return (new DateTime())->setTimestamp($time);
        }

        // Return default
        return $value;
    }

    /**
     * castClass
     */
    private function castClass(EntityClassProperty $entityClassProperty, EntityClassType $type, $value, bool $forceUnknownProperties = false, null|bool $castEntityClasses = null)
    {
        $className = $type->getName();

        // DateTime
        if(strpos($className, "DateTime") !== false)
        {
            return $this->castDateTime($entityClassProperty, $value);
        }

        // Entity
        else if(is_subclass_of($className, EntityClass::class))
        {
            if(is_array($value) && $castEntityClasses)
            {
                // Must return value as object array
                if($type->isArray())
                {
                    // Turn value into sequential if not the case
                    if(!array_is_list($value))
                        $value = [$value];    

                    // Map value
                    return array_map(function($item) use ($className, $forceUnknownProperties)
                    {
                        // Only map arrays into objects, other values are ignored such as values that have already been turned into an object
                        if(is_array($item))
                            return $className::createFromArgs($item, $forceUnknownProperties);
                        
                        // Don't map non-arrays
                        else
                            return $item;
                    }, $value);
                }

                // Must return value as object
                else
                {
                    // Array received, turn into assoc array for conversion
                    if(count($value) && array_is_list($value))
                        $value = reset($value);

                    // Not an array, cannot create entity
                    if(!is_array($value))
                        return $value;

                    // Create entity instance
                    $entityInstance = $className::createFromArgs($value, $forceUnknownProperties);

                    // Return item
                    return $entityInstance;
                }
            }
        }

        return $value;
    }

    /**
     * castValue
     */
    private function castValue(EntityClassProperty $entityClassProperty, EntityClassType $type, $value, bool $forceUnknownProperties = false, bool $castEntityClasses = true)
    {
        $name = $type->getName();

        // Null does not need to be cast
        if($value === null)
            return null;
            
        // Handle
        switch(true)
        {
            // String
            case ($name === "string"):
                
                return $this->castStringValue($value);

            // Int or mixed
            case ($name === "int") && is_numeric($value) && strpos("$value", ".") === false:
                return intval($value);

            // Float, double or mixed
            case ($name === "float" || $name === "double") && is_numeric($value) && strpos("$value", ".") !== false:
                return $name === "float" ? floatval($value) : doubleval($value);

            // Bool or mixed
            case ($name === "bool") && in_array($value, ["0","1","true","false"]):
                return $this->castBooleanValue($value);

            // Mixed
            case ($name === "mixed"):
                return typecast($value);

            // Class
            case class_exists($name):
                if(is_object($value))
                    return $value;
                else
                    return $this->castClass($entityClassProperty, $type, $value, $forceUnknownProperties, $castEntityClasses);

            // Default
            default:
                return $value;
        }
    }

    /**
     * getEntityClassMatch
     * 
     *  Returns the ratio over intersecting class properties intersect value between 0 and 1
     */
    private function getEntityClassMatch(string $className, $value) : float
    {
        $reflection = new ReflectionClass($className);

        // Get property names
        $propertyNames = array_filter(array_map(function($p) { return !$p->isStatic() ? $p->name : false; }, $reflection->getProperties()));

        // Get keys
        $keys = array_keys($value);

        // Get key overlap
        $intersect = array_intersect($keys, $propertyNames);

        // Get intersect ratio
        $ratio = count($intersect) / count($keys);

        // Return ratio
        return $ratio;
    }

    /**
     * parseProperty
     */
    public function parseProperty(EntityClassProperty $property, $value, bool $forceUnknownProperties = false, bool $castEntityClasses = true)
    {
        // Get types
        $types = $property->getTypes();

        // Has more than one class type
        $hasMultipleClassTypes = $property->getTypes(EntityClassProperty::FILTER_CLASS_TYPE);

        // More than one class
        if(is_array($value) && count($hasMultipleClassTypes) > 1)
        {
            $classMatch = [];

            foreach($property->getTypes(EntityClassProperty::FILTER_CLASS_TYPE) as $type)
            {
                $className = $type->getName();
                
                $classMatch[$className] = $this->getEntityClassMatch($className, $value);
            }

            // Get the max key
            $key = array_search(max($classMatch), $classMatch);

            // Resolve the class
            $types = [$property->getTypeByName($key)];
        }

        // Return value
        return $this->castValue($property, reset($types), $value, $forceUnknownProperties, $castEntityClasses);
    }

    /**
     * parse
     */
    public static function parse(EntityClassProperty $entityClassProperty, $value, bool $forceUnknownProperties = false, bool $castEntityClasses = true)
    {
        return (new self())->parseProperty($entityClassProperty, $value, $forceUnknownProperties, $castEntityClasses);
    }
}