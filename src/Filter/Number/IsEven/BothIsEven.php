<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Number\IsEven;

use FiiSoft\Jackdaw\Filter\Number\IsEven;

final class BothIsEven extends IsEven
{
    /**
     * @inheritDoc
     */
    public function isAllowed($value, $key = null): bool
    {
        return ($key & 1) === 0 && ($value & 1) === 0;
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if (($key & 1) === 0 && ($value & 1) === 0) {
                yield $key => $value;
            }
        }
    }
}