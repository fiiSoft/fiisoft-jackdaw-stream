<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\String;

final class Contains extends StringFilter
{
    protected function test(string $value): bool
    {
        if ($this->length > \mb_strlen($value)) {
            return false;
        }
        
        return $this->ignoreCase
            ? \mb_stripos($value, $this->value) !== false
            : \mb_strpos($value, $this->value) !== false;
    }
}