<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper;

use FiiSoft\Jackdaw\Mapper\Internal\StatelessMapper;

final class Value extends StatelessMapper
{
    /**
     * @inheritDoc
     */
    public function map($value, $key = null)
    {
        return $value;
    }
    
    protected function buildKeyMapper(iterable $stream): iterable
    {
        foreach ($stream as $value) {
            yield $value => $value;
        }
    }
    
    public function mergeWith(Mapper $other): bool
    {
        return $other instanceof self;
    }
}