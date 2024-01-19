<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper;

use FiiSoft\Jackdaw\Mapper\Internal\StateMapper;

final class Concat extends StateMapper
{
    private string $separator;
    
    public function __construct(string $separator = '')
    {
        $this->separator = $separator;
    }
    
    /**
     * @inheritDoc
     */
    public function map($value, $key = null): string
    {
        return \implode($this->separator, $value);
    }
    
    protected function buildValueMapper(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            yield $key => \implode($this->separator, $value);
        }
    }
}