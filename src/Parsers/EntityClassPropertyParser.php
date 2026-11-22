<?php
declare(strict_types=1);

namespace gijsbos\Entities\Parsers;

use DateTime;
use gijsbos\Entities\EntityClass;
use gijsbos\Entities\EntityClassProperty;
use gijsbos\Entities\EntityClassType;
use LogicException;
use ReflectionClass;

/**
 * EntityClassPropertyParser
 */
class EntityClassPropertyParser
{
    public static $GLOBAL_DECRYPTER = null;

    private $decrypter = null;

    /**
     * __construct
     */
    public function __construct(mixed $decrypter = null)
    {
        $this->decrypter = $decrypter ?? self::$GLOBAL_DECRYPTER;
    }

    /**
     * decryptStringValue
     */
    private function decryptStringValue(EntityClassProperty $entityClassProperty, $value)
    {
        if(is_string($value) && strval($value) > 0)
        {
            if($entityClassProperty->hasDocProperty("encrypt") || $entityClassProperty->hasDocProperty("encrypted") || $entityClassProperty->hasDocProperty("decrypt"))
            {
                if($this->decrypter == null)
                    throw new LogicException("Could not decrypt value, decrypter not set");

                return call_user_func_array($this->decrypter, [$value]);
            }
        }
        return strval($value);
    }

    /**
     * castStringValue
     */
    private function castStringValue(EntityClassProperty $entityClassProperty, $value)
    {
        if(is_object($value))
        {
            if($value instanceof DateTime)
                return $value->format("c");
        }
        else if(is_array($value))
        {
            return $value;
        }
        else
        {
            return $this->decryptStringValue($entityClassProperty, $value);
        }
    }

    /**
     * castBooleanValue
     */
    private function castBooleanValue($value)
    {
        switch($value)
        {
            case "true":
            case "1":
            case 1: return true;
            case "false":
            case "0":
            case 0: return false;
        }
        
        return $value;
    }

    /**
     * castJsonValue
     */
    private function castJsonValue(EntityClassProperty $entityClassProperty, $value)
    {
        if(is_string($value))
        {
            $value = $this->decryptStringValue($entityClassProperty, $value);

            if(strlen($value) > 0 && is_json($value))
                return json_decode($value, true);
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
                return $this->castStringValue($entityClassProperty, $value);

            // Int or mixed
            case ($name === "int") && is_numeric($value) && strpos("$value", ".") === false:
                return intval($value);

            // Float, double or mixed
            case ($name === "float" || $name === "double") && is_numeric($value) && strpos("$value", ".") !== false:
                return $name === "float" ? floatval($value) : doubleval($value);

            // Bool or mixed
            case ($name === "bool") && in_array($value, [0,1,"0","1","true","false"]):
                return $this->castBooleanValue($value);

            // Mixed
            case ($name === "mixed"):
                return typecast($value);

            // Datetime
            case ($name === "datetime"):
                return is_string($value) ? (new DateTime())->setTimestamp(strtotime($value))->format("c") : $value;

            // Timestamp
            case ($name === "timestamp"):
                return is_string($value) ? strtotime($value) : $value;

            // Json
            case ($name === "json"):
                return $this->castJsonValue($entityClassProperty, $value);

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

        // Empty value, nothing to match
        if(count($keys) === 0)
            return 0.0;

        // Get key overlap
        $intersect = array_intersect($keys, $propertyNames);

        // Get intersect ratio
        $ratio = count($intersect) / count($keys);

        // Return ratio
        return $ratio;
    }

    /**
     * resolveClassType
     *
     *  Returns the class type whose properties match the keys of the value best (the first type on a tie)
     *
     * @param EntityClassType[] $classTypes
     */
    private function resolveClassType(array $classTypes, array $value) : EntityClassType
    {
        $bestType = null;
        $bestMatch = -1.0;

        foreach($classTypes as $type)
        {
            $match = $this->getEntityClassMatch($type->getName(), $value);

            if($match > $bestMatch)
            {
                $bestType = $type;
                $bestMatch = $match;
            }
        }

        return $bestType;
    }

    /**
     * parseProperty
     */
    public function parseProperty(EntityClassProperty $property, $value, bool $forceUnknownProperties = false, bool $castEntityClasses = true)
    {
        // Get types
        $types = $property->getTypes();

        // Has more than one class type
        $classTypes = $property->getTypes(EntityClassProperty::FILTER_CLASS_TYPE);

        // Array of entities with more than one class e.g. A[]|B[] receiving a list: resolve the class per item
        $entityClassArrayTypes = $property->getTypes(EntityClassProperty::FILTER_ENTITY_CLASS_ARRAY_TYPE);

        if(is_array($value) && count($value) > 0 && array_is_list($value) && count($entityClassArrayTypes) > 1 && $castEntityClasses)
        {
            return array_map(function($item) use ($property, $entityClassArrayTypes, $forceUnknownProperties, $castEntityClasses)
            {
                // Only map arrays into objects, other values such as values that have already been turned into an object are ignored
                if(!is_array($item))
                    return $item;

                // Resolve the class of this item
                $className = $this->resolveClassType($entityClassArrayTypes, $item)->getName();

                // Cast the item into a single object of that class
                return $this->castValue($property, new EntityClassType($className, $className), $item, $forceUnknownProperties, $castEntityClasses);
            }, $value);
        }

        // More than one class
        if(is_array($value) && count($classTypes) > 1)
        {
            // Resolve the class
            $types = [$this->resolveClassType($classTypes, $value)];
        }

        $firstType = reset($types);
        
        // When a property has not defined any type e.g. @var string <name>, the types array is empty so we cannot parse the property
        if($firstType === false)
        {
            return $value;
        }

        // Return value
        return $this->castValue($property, $firstType, $value, $forceUnknownProperties, $castEntityClasses);
    }

    /**
     * parse
     */
    public static function parse(EntityClassProperty $entityClassProperty, $value, bool $forceUnknownProperties = false, bool $castEntityClasses = true)
    {
        return (new self())->parseProperty($entityClassProperty, $value, $forceUnknownProperties, $castEntityClasses);
    }
}