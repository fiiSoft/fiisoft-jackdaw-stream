<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper;

use FiiSoft\Jackdaw\Mapper\Internal\BaseMapper;

final class Trim extends BaseMapper
{
    private string $chars;
    
    public function __construct(string $chars = " \t\n\r\0\x0B")
    {
        $this->chars = $chars;
    }
    
    /**
     * @inheritDoc
     */
    public function map($value, $key)
    {
        if (\is_string($value)) {
            return \trim($value, $this->chars);
        }
        
        return $value;
    }
    
    public function mergeWith(Mapper $other): bool
    {
        if ($other instanceof self) {
            $this->chars = \implode(\array_unique(\mb_str_split($this->chars.$other->chars), \SORT_REGULAR));
            return true;
        }
        
        return false;
    }
}