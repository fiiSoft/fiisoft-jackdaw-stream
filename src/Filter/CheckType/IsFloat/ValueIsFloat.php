<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\CheckType\IsFloat;

use FiiSoft\Jackdaw\Filter\CheckType\IsFloat;

final class ValueIsFloat extends IsFloat
{
    /**
     * @inheritDoc
     */
    public function isAllowed($value, $key = null): bool
    {
        return \is_float($value);
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if (\is_float($value)) {
                yield $key => $value;
            }
        }
    }
}