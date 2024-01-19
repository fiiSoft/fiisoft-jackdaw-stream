<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\CheckType\IsCountable;

use FiiSoft\Jackdaw\Filter\CheckType\IsCountable;

final class BothIsCountable extends IsCountable
{
    /**
     * @inheritDoc
     */
    public function isAllowed($value, $key = null): bool
    {
        return \is_countable($value) && \is_countable($key);
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if (\is_countable($value) && \is_countable($key)) {
                yield $key => $value;
            }
        }
    }
}