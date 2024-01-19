<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\CheckType\IsNull;

use FiiSoft\Jackdaw\Filter\CheckType\IsNull;

final class BothIsNull extends IsNull
{
    /**
     * @inheritDoc
     */
    public function isAllowed($value, $key = null): bool
    {
        return $key === null && $value === null;
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if ($key === null && $value === null) {
                yield $key => $value;
            }
        }
    }
}