<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\CheckType\IsNumeric;

use FiiSoft\Jackdaw\Filter\CheckType\IsNumeric;

final class BothIsNumeric extends IsNumeric
{
    /**
     * @inheritDoc
     */
    public function isAllowed($value, $key = null): bool
    {
        return \is_numeric($value) && \is_numeric($key);
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if (\is_numeric($key) && \is_numeric($value)) {
                yield $key => $value;
            }
        }
    }
}