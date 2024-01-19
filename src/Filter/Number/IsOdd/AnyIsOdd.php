<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Number\IsOdd;

use FiiSoft\Jackdaw\Filter\Number\IsOdd;

final class AnyIsOdd extends IsOdd
{
    /**
     * @inheritdoc
     */
    public function isAllowed($value, $key = null): bool
    {
        return ($key & 1) === 1 || ($value & 1) === 1;
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if (($key & 1) === 1 || ($value & 1) === 1) {
                yield $key => $value;
            }
        }
    }
}