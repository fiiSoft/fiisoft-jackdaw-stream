<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\CheckType\IsEmpty;

use FiiSoft\Jackdaw\Filter\CheckType\IsEmpty;

final class ValueIsEmpty extends IsEmpty
{
    /**
     * @inheritDoc
     */
    public function isAllowed($value, $key = null): bool
    {
        return empty($value);
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if (empty($value)) {
                yield $key => $value;
            }
        }
    }
}