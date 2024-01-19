<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\CheckType\IsNumeric;

use FiiSoft\Jackdaw\Filter\CheckType\IsNumeric;

final class ValueIsNumeric extends IsNumeric
{
    /**
     * @inheritDoc
     */
    public function isAllowed($value, $key = null): bool
    {
        return \is_numeric($value);
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if (\is_numeric($value)) {
                yield $key => $value;
            }
        }
    }
}