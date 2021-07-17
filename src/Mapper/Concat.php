<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper;

final class Concat implements Mapper
{
    /** @var string */
    private $separator;
    
    public function __construct(string $separator = '')
    {
        $this->separator = $separator;
    }
    
    public function map($value, $key)
    {
        if (\is_array($value)) {
            return \implode($this->separator, $value);
        }
    
        throw new \LogicException('Unable to concat something which is not an array');
    }
}