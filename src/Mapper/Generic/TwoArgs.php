<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper\Generic;

use FiiSoft\Jackdaw\Mapper\GenericMapper;

final class TwoArgs extends GenericMapper
{
    /**
     * @inheritDoc
     */
    public function map($value, $key = null)
    {
        return ($this->callable)($value, $key);
    }
    
    protected function buildValueMapper(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            yield $key => ($this->callable)($value, $key);
        }
    }
}