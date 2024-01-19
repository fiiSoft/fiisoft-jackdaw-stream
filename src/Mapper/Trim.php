<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper;

use FiiSoft\Jackdaw\Mapper\Internal\StateMapper;

final class Trim extends StateMapper
{
    private string $chars;
    
    public function __construct(string $chars = " \t\n\r\0\x0B")
    {
        $this->chars = $chars;
    }
    
    /**
     * @inheritDoc
     */
    public function map($value, $key = null): string
    {
        return \trim($value, $this->chars);
    }
    
    protected function buildValueMapper(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            yield $key => \trim($value, $this->chars);
        }
    }
    
    public function mergeWith(Mapper $other): bool
    {
        if ($other instanceof self) {
            $this->chars = \implode('', \array_unique(\mb_str_split($this->chars.$other->chars), \SORT_REGULAR));
            return true;
        }
        
        return false;
    }
}