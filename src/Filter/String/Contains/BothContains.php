<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\String\Contains;

use FiiSoft\Jackdaw\Filter\String\Contains;

final class BothContains extends Contains
{
    /**
     * @inheritDoc
     */
    public function isAllowed($value, $key = null): bool
    {
        if ($this->ignoreCase) {
            return \mb_stripos($key, $this->value) !== false && \mb_stripos($value, $this->value) !== false;
        }
        
        return \mb_strpos($key, $this->value) !== false && \mb_strpos($value, $this->value) !== false;
    }
    
    protected function compareCaseInsensitive(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if (\mb_stripos($key, $this->value) !== false && \mb_stripos($value, $this->value) !== false) {
                yield $key => $value;
            }
        }
    }
    
    protected function compareCaseSensitive(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if (\mb_strpos($key, $this->value) !== false && \mb_strpos($value, $this->value) !== false) {
                yield $key => $value;
            }
        }
    }
}