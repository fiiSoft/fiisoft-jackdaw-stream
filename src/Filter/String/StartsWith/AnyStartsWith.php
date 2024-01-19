<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\String\StartsWith;

use FiiSoft\Jackdaw\Filter\String\StartsWith;

final class AnyStartsWith extends StartsWith
{
    /**
     * @inheritDoc
     */
    public function isAllowed($value, $key = null): bool
    {
        if ($this->ignoreCase) {
            return \mb_stripos($value, $this->value) === 0 || \mb_stripos($key, $this->value) === 0;
        }
        
        return \mb_strpos($value, $this->value) === 0 || \mb_strpos($key, $this->value) === 0;
    }
    
    protected function compareCaseInsensitive(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if (\mb_stripos($value, $this->value) === 0 || \mb_stripos($key, $this->value) === 0) {
                yield $key => $value;
            }
        }
    }
    
    protected function compareCaseSensitive(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if (\mb_strpos($value, $this->value) === 0 || \mb_strpos($key, $this->value) === 0) {
                yield $key => $value;
            }
        }
    }
}