<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Number\IsOdd;

use FiiSoft\Jackdaw\Filter\Number\IsOdd;

final class ValueIsOdd extends IsOdd
{
    /**
     * @inheritdoc
     */
    public function isAllowed($value, $key = null): bool
    {
        return ($value & 1) === 1;
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if (($value & 1) === 1) {
                yield $key => $value;
            }
        }
    }
}