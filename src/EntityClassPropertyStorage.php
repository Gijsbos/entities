<?php
declare(strict_types=1);

namespace gijsbos\Entities;

/**
 * EntityClassPropertyStorage
 */
class EntityClassPropertyStorage
{
    public static array $STORAGE = [];

    public static function setValue(int $objectId, string $key, $value)
    {
        if(!array_key_exists($objectId, self::$STORAGE))
            self::$STORAGE[$objectId] = [];

        self::$STORAGE[$objectId][$key] = $value;
    }

    public static function getValue(int $objectId, string $key)
    {
        return @self::$STORAGE[$objectId][$key];
    }
}