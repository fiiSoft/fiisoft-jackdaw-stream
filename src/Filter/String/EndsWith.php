<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\String;

final class EndsWith extends StringFilter
{
    protected function test(string $value): bool
    {
        $length = \mb_strlen($value);
        
        if ($this->length > $length) {
            return false;
        }
        
        return $this->ignoreCase
            ? \mb_stripos($value, $this->value, $length - $this->length) !== false
            : \mb_strpos($value, $this->value, $length - $this->length) !== false;
    }
}