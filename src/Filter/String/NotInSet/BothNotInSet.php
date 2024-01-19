<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\String\NotInSet;

use FiiSoft\Jackdaw\Filter\String\NotInSet;

final class BothNotInSet extends NotInSet
{
    /**
     * @inheritDoc
     */
    public function isAllowed($value, $key = null): bool
    {
        if ($this->ignoreCase) {
            return (!\is_string($value) || !isset($this->values[\mb_strtolower($value)]))
                && (!\is_string($key) || !isset($this->values[\mb_strtolower($key)]));
        }
        
        return (!\is_string($value) || !isset($this->values[$value]))
            && (!\is_string($key) || !isset($this->values[$key]));
    }
    
    protected function compareCaseInsensitive(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if (
                   (!\is_string($value) || !isset($this->values[\mb_strtolower($value)]))
                && (!\is_string($key) || !isset($this->values[\mb_strtolower($key)]))
            ) {
                yield $key => $value;
            }
        }
    }
    
    protected function compareCaseSensitive(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if (
                   (!\is_string($value) || !isset($this->values[$value]))
                && (!\is_string($key) || !isset($this->values[$key]))
            ) {
                yield $key => $value;
            }
        }
    }
}