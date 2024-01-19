<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\String\NotInSet;

use FiiSoft\Jackdaw\Filter\String\NotInSet;

final class ValueNotInSet extends NotInSet
{
    /**
     * @inheritDoc
     */
    public function isAllowed($value, $key = null): bool
    {
        if (\is_string($value)) {
            return $this->ignoreCase
                ? !isset($this->values[\mb_strtolower($value)])
                : !isset($this->values[$value]);
        }
        
        return true;
    }
    
    protected function compareCaseInsensitive(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if (!\is_string($value) || !isset($this->values[\mb_strtolower($value)])) {
                yield $key => $value;
            }
        }
    }
    
    protected function compareCaseSensitive(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if (!\is_string($value) || !isset($this->values[$value])) {
                yield $key => $value;
            }
        }
    }
}