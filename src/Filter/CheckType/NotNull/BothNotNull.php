<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\CheckType\NotNull;

use FiiSoft\Jackdaw\Filter\CheckType\NotNull;

final class BothNotNull extends NotNull
{
    /**
     * @inheritDoc
     */
    public function isAllowed($value, $key = null): bool
    {
        return $value !== null && $key !== null;
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if ($value !== null && $key !== null) {
                yield $key => $value;
            }
        }
    }
}