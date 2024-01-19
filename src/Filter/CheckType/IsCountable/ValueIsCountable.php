<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\CheckType\IsCountable;

use FiiSoft\Jackdaw\Filter\CheckType\IsCountable;

final class ValueIsCountable extends IsCountable
{
    /**
     * @inheritDoc
     */
    public function isAllowed($value, $key = null): bool
    {
        return \is_countable($value);
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if (\is_countable($value)) {
                yield $key => $value;
            }
        }
    }
}