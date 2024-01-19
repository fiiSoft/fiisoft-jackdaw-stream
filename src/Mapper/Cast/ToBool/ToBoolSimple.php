<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper\Cast\ToBool;

use FiiSoft\Jackdaw\Mapper\Internal\SimpleCastMapper;
use FiiSoft\Jackdaw\Mapper\Cast\ToBool;

final class ToBoolSimple extends ToBool
{
    use SimpleCastMapper;
    
    /**
     * @inheritDoc
     */
    public function map($value, $key = null): bool
    {
        return (bool) $value;
    }
    
    protected function buildValueMapper(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            yield $key => (bool) $value;
        }
    }
}