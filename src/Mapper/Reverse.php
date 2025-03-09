<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper;

use FiiSoft\Jackdaw\Mapper\Exception\MapperExceptionFactory;
use FiiSoft\Jackdaw\Mapper\Internal\StatelessMapper;

final class Reverse extends StatelessMapper
{
    /**
     * @inheritDoc
     */
    public function map($value, $key = null)
    {
        if (\is_array($value)) {
            return \array_reverse($value, true);
        }
    
        if (\is_string($value)) {
            return \strrev($value);
        }
        
        throw MapperExceptionFactory::unableToReverse($value);
    }
    
    protected function buildValueMapper(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if (\is_array($value)) {
                yield $key => \array_reverse($value, true);
            } elseif (\is_string($value)) {
                yield $key => \strrev($value);
            } else {
                throw MapperExceptionFactory::unableToReverse($value);
            }
        }
    }
}