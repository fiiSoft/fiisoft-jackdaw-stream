<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\CheckType\NotNull;

use FiiSoft\Jackdaw\Filter\CheckType\NotNull;

final class KeyNotNull extends NotNull
{
    /**
     * @inheritDoc
     */
    public function isAllowed($value, $key = null): bool
    {
        return $key !== null;
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if ($key !== null) {
                yield $key => $value;
            }
        }
    }
}