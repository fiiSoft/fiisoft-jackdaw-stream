<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper\Cast\ToFloat;

use FiiSoft\Jackdaw\Mapper\Internal\SimpleCastMapper;
use FiiSoft\Jackdaw\Mapper\Cast\ToFloat;

final class ToFloatSimple extends ToFloat
{
    use SimpleCastMapper;
    
    /**
     * @inheritDoc
     */
    public function map($value, $key = null): float
    {
        return (float) $value;
    }
    
    protected function buildValueMapper(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            yield $key => (float) $value;
        }
    }
}