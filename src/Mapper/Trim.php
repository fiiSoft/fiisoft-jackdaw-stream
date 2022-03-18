<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper;

final class Trim implements Mapper
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
}