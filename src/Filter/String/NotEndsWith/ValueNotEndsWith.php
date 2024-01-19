<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\String\NotEndsWith;

use FiiSoft\Jackdaw\Filter\String\NotEndsWith;

final class ValueNotEndsWith extends NotEndsWith
{
    /**
     * @inheritDoc
     */
    public function isAllowed($value, $key = null): bool
    {
        $length = \mb_strlen($value);
        
        if ($length < $this->length) {
            return true;
        }
        
        return $this->ignoreCase
            ? \mb_stripos($value, $this->value, $length - $this->length) === false
            : \mb_strpos($value, $this->value, $length - $this->length) === false;
    }
    
    protected function compareCaseInsensitive(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            $length = \mb_strlen($value);
            if ($length < $this->length || \mb_stripos($value, $this->value, $length - $this->length) === false) {
                yield $key => $value;
            }
        }
    }
    
    protected function compareCaseSensitive(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            $length = \mb_strlen($value);
            if ($length < $this->length || \mb_strpos($value, $this->value, $length - $this->length) === false) {
                yield $key => $value;
            }
        }
    }
}