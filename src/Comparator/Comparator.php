<?php

namespace FiiSoft\Jackdaw\Comparator;

interface Comparator extends ComparisonSpec
{
    /**
     * @param mixed $value1
     * @param mixed $value2
     * @return int 0 when the first element is equal to the second, <0 when smaller, >0 when greater
     */
    public function compare($value1, $value2): int;
    
    /**
     * @param mixed $value1
     * @param mixed $value2
     * @param mixed $key1
     * @param mixed $key2
     * @return int 0 when the first element is equal to the second, <0 when smaller, >0 when greater
     */
    public function compareAssoc($value1, $value2, $key1, $key2): int;
}