<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\String\EndsWith;

use FiiSoft\Jackdaw\Filter\String\EndsWith;

final class ValueEndsWith extends EndsWith
{
    /**
     * @inheritDoc
     */
    public function isAllowed($value, $key = null): bool
    {
        $length = \mb_strlen($value);
        
        if ($length < $this->length) {
            return false;
        }
        
        return $this->ignoreCase
            ? \mb_stripos($value, $this->value, $length - $this->length) !== false
            : \mb_strpos($value, $this->value, $length - $this->length) !== false;
    }
    
    protected function compareCaseInsensitive(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            $length = \mb_strlen($value);
            if ($length >= $this->length && \mb_stripos($value, $this->value, $length - $this->length) !== false) {
                yield $key => $value;
            }
        }
    }
    
    protected function compareCaseSensitive(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            $length = \mb_strlen($value);
            if ($length >= $this->length && \mb_strpos($value, $this->value, $length - $this->length) !== false) {
                yield $key => $value;
            }
        }
    }
}