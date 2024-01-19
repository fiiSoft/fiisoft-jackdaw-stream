<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\CheckType\IsString;

use FiiSoft\Jackdaw\Filter\CheckType\IsString;

final class AnyIsString extends IsString
{
    /**
     * @inheritDoc
     */
    public function isAllowed($value, $key = null): bool
    {
        return \is_string($value) || \is_string($key);
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if (\is_string($value) || \is_string($key)) {
                yield $key => $value;
            }
        }
    }
}