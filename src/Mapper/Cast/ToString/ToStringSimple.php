<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper\Cast\ToString;

use FiiSoft\Jackdaw\Mapper\Internal\SimpleCastMapper;
use FiiSoft\Jackdaw\Mapper\Cast\ToString;

final class ToStringSimple extends ToString
{
    use SimpleCastMapper;
    
    /**
     * @inheritDoc
     */
    public function map($value, $key = null): string
    {
        return (string) $value;
    }
    
    protected function buildValueMapper(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            yield $key => (string) $value;
        }
    }
}