<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Matcher;

interface Matcher
{
    /**
     * @param mixed $value1
     * @param mixed $value2
     * @param mixed $key1
     * @param mixed $key2
     */
    public function matches($value1, $value2, $key1 = null, $key2 = null): bool;
    
    public function equals(Matcher $other): bool;
}