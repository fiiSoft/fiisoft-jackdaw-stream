<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper\Generic;

use FiiSoft\Jackdaw\Mapper\GenericMapper;

final class ZeroArg extends GenericMapper
{
    /**
     * @inheritDoc
     */
    public function map($value, $key = null)
    {
        return ($this->callable)();
    }
    
    protected function buildValueMapper(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            yield $key => ($this->callable)();
        }
    }
}