<?php
declare(strict_types=1);

namespace gijsbos\Entities\Attributes;

use Attribute;
use InvalidArgumentException;
use LogicException;
use ReflectionClass;

#[Attribute(Attribute::TARGET_PROPERTY)]
class PropertyMapper
{
    public function __construct(private mixed $mapper)
    {
        if(!is_callable($mapper))
            throw new InvalidArgumentException("Invalid argument 'mapper', expected callable");
    }

    public function getMapper()
    {
        return $this->mapper;
    }

    public function execute(mixed $input)
    {
        $mapper = $this->mapper;
        return $mapper($input);
    }
}