<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Matcher\Generic;

use FiiSoft\Jackdaw\Matcher\Matcher;

abstract class BaseGenericMatcher implements Matcher
{
    /** @var callable */
    protected $callable;
    
    public function __construct(callable $callable)
    {
        $this->callable = $callable;
    }
    
    final public function equals(Matcher $other): bool
    {
        return $other === $this || $other instanceof $this && $other->callable === $this->callable;
    }
}