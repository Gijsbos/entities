<?php
declare(strict_types=1);

namespace gijsbos\Entities;

use Exception;

/**
 * EntityClassReflectionList
 */
class EntityClassReflectionList
{
    /**
     * @var EntityClassReflection[] list
     */
    private array $list;

    /**
     * __construct
     */
    public function __construct()
    {
        $this->list = [];    
    }

    /**
     * getList
     */
    public function getList() : array
    {
        return $this->list;
    }

    /**
     * getKeys
     */
    public function getKeys() : array
    {
        return array_keys($this->list);
    }

    /**
     * add
     */
    public function add(EntityClassReflection $entityClassReflection)
    {
        $this->list[$entityClassReflection->getName()] = $entityClassReflection;
    }

    /**
     * has
     */
    public function has(string $entityClassName)
    {
        return array_key_exists($entityClassName, $this->list);
    }

    /**
     * get
     */
    public function get(string $entityClassName)
    {
        if(!$this->has($entityClassName))
            throw new Exception("Could not find entity class '$entityClassName'");

        return $this->list[$entityClassName];
    }

    /**
     * merge
     */
    public function merge(EntityClassReflectionList $entityClassReflectionList)
    {
        $this->list = array_merge($this->list, $entityClassReflectionList->getList());
    }
}