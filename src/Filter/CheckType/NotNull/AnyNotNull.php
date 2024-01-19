<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\CheckType\NotNull;

use FiiSoft\Jackdaw\Filter\CheckType\NotNull;

final class AnyNotNull extends NotNull
{
    /**
     * @inheritDoc
     */
    public function isAllowed($value, $key = null): bool
    {
        return $key !== null || $value !== null;
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if ($key !== null || $value !== null) {
                yield $key => $value;
            }
        }
    }
}