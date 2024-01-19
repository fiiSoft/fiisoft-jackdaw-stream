<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\CheckType\IsNumeric;

use FiiSoft\Jackdaw\Filter\CheckType\IsNumeric;

final class KeyIsNumeric extends IsNumeric
{
    /**
     * @inheritDoc
     */
    public function isAllowed($value, $key = null): bool
    {
        return \is_numeric($key);
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if (\is_numeric($key)) {
                yield $key => $value;
            }
        }
    }
}