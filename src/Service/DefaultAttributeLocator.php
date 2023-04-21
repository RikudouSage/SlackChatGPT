<?php

namespace App\Service;

use LogicException;
use ReflectionObject;

final class DefaultAttributeLocator implements AttributeLocator
{
    public function hasAttribute(object $target, string $attribute): bool
    {
        $reflection = new ReflectionObject($target);
        $attributes = $reflection->getAttributes($attribute);

        return count($attributes) > 0;
    }

    public function getAttribute(object $target, string $attribute): object
    {
        if (!$this->hasAttribute($target, $attribute)) {
            throw new LogicException(sprintf(
                "The attribute '%s' doesn't exist on object of type '%s'.",
                $attribute,
                $target::class,
            ));
        }

        $reflection = new ReflectionObject($target);
        $attributes = $reflection->getAttributes($attribute);

        return $attributes[array_key_first($attributes)]->newInstance();
    }
}
