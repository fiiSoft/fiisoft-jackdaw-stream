<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\CheckType\IsNull;

use FiiSoft\Jackdaw\Filter\CheckType\IsNull;

final class ValueIsNull extends IsNull
{
    /**
     * @inheritDoc
     */
    public function isAllowed($value, $key = null): bool
    {
        return $value === null;
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if ($value === null) {
                yield $key => $value;
            }
        }
    }
}