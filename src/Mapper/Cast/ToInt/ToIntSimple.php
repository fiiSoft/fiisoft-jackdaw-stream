<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper\Cast\ToInt;

use FiiSoft\Jackdaw\Mapper\Internal\SimpleCastMapper;
use FiiSoft\Jackdaw\Mapper\Cast\ToInt;

final class ToIntSimple extends ToInt
{
    use SimpleCastMapper;
    
    /**
     * @inheritDoc
     */
    public function map($value, $key = null): int
    {
        return (int) $value;
    }
    
    protected function buildValueMapper(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            yield $key => (int) $value;
        }
    }
}