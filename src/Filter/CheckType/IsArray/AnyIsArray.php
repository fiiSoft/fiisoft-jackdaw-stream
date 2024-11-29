<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\CheckType\IsArray;

use FiiSoft\Jackdaw\Filter\CheckType\IsArray;

final class AnyIsArray extends IsArray
{
    /**
     * @inheritDoc
     */
    public function isAllowed($value, $key = null): bool
    {
        return \is_array($value) || \is_array($key);
    }
    
    /**
     * @inheritDoc
     */
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if (\is_array($value) || \is_array($key)) {
                yield $key => $value;
            }
        }
    }
}