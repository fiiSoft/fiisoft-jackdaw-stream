<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\CheckType\IsInt;

use FiiSoft\Jackdaw\Filter\CheckType\IsInt;

final class KeyIsInt extends IsInt
{
    /**
     * @inheritDoc
     */
    public function isAllowed($value, $key = null): bool
    {
        return \is_int($key);
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if (\is_int($key)) {
                yield $key => $value;
            }
        }
    }
}