<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper\Cast\ToTime;

use FiiSoft\Jackdaw\Mapper\Cast\ToTime;
use FiiSoft\Jackdaw\Mapper\Internal\SimpleCastMapper;

final class ToTimeSimple extends ToTime
{
    use SimpleCastMapper;
    
    /**
     * @inheritDoc
     */
    public function map($value, $key = null): \DateTimeImmutable
    {
        return $this->cast($value);
    }
    
    protected function buildValueMapper(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            yield $key => $this->cast($value);
        }
    }
}