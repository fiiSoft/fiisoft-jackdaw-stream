<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\CheckType\IsBool;

use FiiSoft\Jackdaw\Filter\CheckType\IsBool;

final class ValueIsBool extends IsBool
{
    /**
     * @inheritDoc
     */
    public function isAllowed($value, $key = null): bool
    {
        return \is_bool($value);
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if (\is_bool($value)) {
                yield $key => $value;
            }
        }
    }
}