<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Comparator;

use FiiSoft\Jackdaw\Internal\Helper;

/**
 * Allows to compare length of strings and size of arrays. Can also handle \Countable as well.
 */
final class SizeComparator implements Comparator
{
    /**
     * @inheritDoc
     */
    public function compare($value1, $value2): int
    {
        return $this->sizeOf($value1) <=> $this->sizeOf($value2);
    }
    
    /**
     * @inheritDoc
     */
    public function compareAssoc($value1, $value2, $key1, $key2): int
    {
        return $this->sizeOf($value1) <=> $this->sizeOf($value2) ?: $key1 <=> $key2;
    }
    
    private function sizeOf($value): int
    {
        if (\is_array($value)) {
            return \count($value);
        }
        
        if ($value instanceof \Countable) {
            return $value->count();
        }
        
        if (\is_string($value)) {
            return \mb_strlen($value);
        }
        
        throw new \LogicException('Cannot compute size of '.Helper::typeOfParam($value));
    }
}