<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\String\NotContains;

use FiiSoft\Jackdaw\Filter\String\NotContains;

final class KeyNotContains extends NotContains
{
    /**
     * @inheritDoc
     */
    public function isAllowed($value, $key = null): bool
    {
        return $this->ignoreCase
            ? \mb_stripos($key, $this->value) === false
            : \mb_strpos($key, $this->value) === false;
    }
    
    protected function compareCaseInsensitive(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if (\mb_stripos($key, $this->value) === false) {
                yield $key => $value;
            }
        }
    }
    
    protected function compareCaseSensitive(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if (\mb_strpos($key, $this->value) === false) {
                yield $key => $value;
            }
        }
    }
}