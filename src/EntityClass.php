<?php
declare(strict_types=1);

namespace gijsbos\Entities;

use Exception;
use ReflectionClass;
use stdClass;
use gijsbos\Entities\Parsers\EntityClassPropertyValueParser;

/**
 * EntityClass
 */
class EntityClass extends stdClass
{
    /**
     * getEntityClassName
     */
    public static function getEntityClassName()
    {
        return (new ReflectionClass(get_called_class()))->getName();
    }

    /**
     * getEntityClassReflectionClassName
     */
    public static function getEntityClassReflectionClassName() : string
    {
        return EntityClassReflection::class;
    }

    /**
     * getEntityClassReflection
     */
    public static function getEntityClassReflection()
    {
        return EntityClassReflection::getEntityClassReflection(get_called_class());
    }

    /**
     * getEntityClassPropertyClassName
     */
    public static function getEntityClassPropertyClassName() : string
    {
        return EntityClassProperty::class;
    }

    /**
     * getEntityClassProperty
     */
    public static function getEntityClassProperty(string $propertyName, bool $throws = true) : false | EntityClassProperty
    {
        return self::getEntityClassReflection()->getEntityClassProperty($propertyName, $throws);
    }

    /**
     * createEntityClassInstanceForProperty
     */
    public function createEntityClassInstanceFromProperty(string $propertyName)
    {
        // Make sure that methods that overwrite the default method get called
        $calledClass = get_called_class();

        // Get EntityClassReflection
        $reflection = $this->getEntityClassReflection();

        // Verify that method exists
        if($reflection->getProperty($propertyName))
        {
            $property = $reflection->getEntityClassProperty($propertyName);

            // Get class types
            $classTypes = $property->getClassTypes();

            // No class types
            if(count($classTypes) == 0)
                throw new Exception(__METHOD__ . " failed: Property $propertyName does not have a type of type class");

            // Get first type
            $type = reset($classTypes);

            // Get edge name
            return $calledClass::createEntityClass($type->getName());
        }

        // Throw
        throw new Exception(__METHOD__ . " failed: Entity class for property $propertyName could not be found");
    }

    /**
     * createEntityClass
     * 
     * @return object
     */
    public static function createEntityClass(null|string $className = null)
    {
        // Get called function
        $calledClass = $className ? $className : get_called_class();
        
        // Create instance
        $instance = (new ReflectionClass($calledClass))->newInstanceWithoutConstructor();

        // Return instance
        return $instance;
    }

    /**
     * processCustomProperty
     */
    private function processCustomProperty($customProperty, string $propertyName, $value)
    {
        if($customProperty instanceof EntityClassProperty)
        {
            $this->$propertyName = EntityClassPropertyValueParser::parse($customProperty, $value);
        }
        else if(is_callable($customProperty))
        {
            $this->$propertyName = @call_user_func_array($customProperty, [$propertyName]);
        }
        else
        {
            $this->$propertyName = @$customProperty;
        }
    }

    /**
     * setProperties
     */
    public function setProperties(array|object $args, bool $forceUnknownProperties = false, array $customProperties = [], bool $castEntityClasses = true)
    {
        $entityClassReflection = self::getEntityClassReflection();

        // Turn args into an array
        $args = is_object($args) ? (array) $args : $args;

        // Set properties
        if(is_array($args))
        {
            // Iterate over args
            foreach($args as $propertyName => $value)
            {
                // Check custom properties
                if($forceUnknownProperties && array_key_exists($propertyName, $customProperties))
                {
                    $this->processCustomProperty($customProperties[$propertyName], $propertyName, $value);

                    // Proceed
                    continue;
                }

                // Only process assoc values
                if(is_string($propertyName))
                {
                    // Get current value
                    $objectValue = @$this->$propertyName;
                    
                    // Value is an object
                    if(is_object($objectValue))
                    {
                        if(is_array($value) && is_subclass_of($objectValue, EntityClass::class) && $castEntityClasses)
                        {
                            $objectValue->setProperties($value, $forceUnknownProperties);
                        }
                    }

                    // Value is not an object
                    else
                    {
                        // Lookup the entity class property in case it needs to be parsed further
                        // If forceUnknownProperties is set, an exception is thrown when the property is not found, else the value is set
                        $entityClassProperty = $entityClassReflection->getEntityClassProperty($propertyName, !$forceUnknownProperties);

                        // Entity class property found
                        if($entityClassProperty !== false)
                            $this->$propertyName = EntityClassPropertyValueParser::parse($entityClassProperty, $value, $forceUnknownProperties);

                        // Entity class property not found
                        else
                            $this->$propertyName = $value;
                    }
                }
            }
        }
    }

    /**
     * createFromArgs
     */
    public static function createFromArgs(null|array $args = null, bool $forceUnknownProperties = false, array $customProperties = [], bool $castEntityClasses = true)
    {
        // Create instance
        $instance = (object) self::createEntityClass();
        
        // Set properties
        $instance->setProperties($args, $forceUnknownProperties, $customProperties, $castEntityClasses);
        
        // Return 
        return $instance;
    }

    /**
     * __extractDocProperty
     */
    public function __extractDocProperty(string $className, string $classProperty, string $docProperty)
    {
        $entityClassReflectionClassName = $className::getEntityClassReflectionClassName();

        // Get reflection
        $entityClassReflection = $entityClassReflectionClassName::getEntityClassReflection($className);

        // Lookup property
        if($entityClassReflection->hasEntityClassProperty($classProperty))
        {
            $entityClassProperty = $entityClassReflection->getEntityClassProperty($classProperty);

            if($entityClassProperty->hasDocProperty($docProperty))
            {
                return $entityClassProperty->getDocProperty($docProperty);
            }
        }

        // Empty
        return null;
    }

    /**
     * __handleCustomEntityCall
     */
    private function __handleCustomEntityCall($method, &$arguments)
    {
        $className = get_called_class();

        // Check of property is being accessed
        if(property_exists($className, $method))
            return $this->__extractDocProperty($className, $method, array_shift($arguments));

        // Undefined methods
        else
            throw new Exception(sprintf("Call to undefined method %s::%s", $className, $method));
    }

    /**
     * __call
     */
    public function __call($method, $arguments)
    {
        return $this->__handleCustomEntityCall($method, $arguments);
    }

    /**
     * __callStatic
     */
    public static function __callStatic($method, $arguments)
    {
        $className = get_called_class();

        // Create instance and process
        return (new $className())->__handleCustomEntityCall($method, $arguments);
    }

    /**
     * getPropertyList
     */
    public static function getPropertyList($filter = null)
    {
        return self::getEntityClassReflection()->getPropertyList(get_called_class(), $filter);
    }

    /**
     * getPropertyKeyList
     */
    public static function getPropertyKeyList($filter = null)
    {
        return array_keys(self::getEntityClassReflection()->getPropertyList(get_called_class(), $filter));
    }

    /**
     * toArray
     *  Converts every object within the current object to array recursively
     */
    public function toArray() : array
    {
        $self = (array) $this;

        array_walk_recursive($self, function(&$value)
        {
            if(is_object($value))
                $value = (array) $value;
        });

        return $self;
    }

    // /**
    //  * export
    //  */
    // public function export(...$keys) : array
    // {
    //     if(count($keys) && is_array($keys[0]))
    //         $keys = $keys[0];

    //     return array_filter_keys((array) $this, $keys);
    // }
}