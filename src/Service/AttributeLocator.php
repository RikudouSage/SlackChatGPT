<?php

namespace App\Service;

interface AttributeLocator
{
    public function hasAttribute(object $target, string $attribute): bool;

    /**
     * @template T of object
     *
     * @param class-string<T> $attribute
     *
     * @return T
     */
    public function getAttribute(object $target, string $attribute): object;
}
