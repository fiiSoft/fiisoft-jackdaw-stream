<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Number\IsEven;

use FiiSoft\Jackdaw\Filter\Number\IsEven;

final class ValueIsEven extends IsEven
{
    /**
     * @inheritDoc
     */
    public function isAllowed($value, $key = null): bool
    {
        return ($value & 1) === 0;
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if (($value & 1) === 0) {
                yield $key => $value;
            }
        }
    }
}