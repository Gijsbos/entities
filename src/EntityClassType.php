<?php
declare(strict_types=1);

namespace gijsbos\Entities;

/**
 * EntityClassType
 */
class EntityClassType
{
    /**
     * @var string declaredType
     */
    private $declaredType;

    /**
     * @var string name
     */
    private $name;

    /**
     * __construct
     */
    public function __construct(string $declaredType, string $name)
    {
        $this->declaredType = $declaredType;
        $this->name = $name;
    }

    /**
     * getDeclaredType
     */
    public function getDeclaredType()
    {
        return $this->declaredType;
    }

    /**
     * getName
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * isArray
     */
    public function isArray()
    {
        return str_ends_with($this->declaredType, "[]") || $this->name === "array";
    }
}