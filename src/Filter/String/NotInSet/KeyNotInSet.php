<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\String\NotInSet;

use FiiSoft\Jackdaw\Filter\String\NotInSet;

final class KeyNotInSet extends NotInSet
{
    /**
     * @inheritDoc
     */
    public function isAllowed($value, $key = null): bool
    {
        if (\is_string($key)) {
            return $this->ignoreCase
                ? !isset($this->values[\mb_strtolower($key)])
                : !isset($this->values[$key]);
        }
        
        return true;
    }
    
    protected function compareCaseInsensitive(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if (!\is_string($key) || !isset($this->values[\mb_strtolower($key)])) {
                yield $key => $value;
            }
        }
    }
    
    protected function compareCaseSensitive(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if (!\is_string($key) || !isset($this->values[$key])) {
                yield $key => $value;
            }
        }
    }
}