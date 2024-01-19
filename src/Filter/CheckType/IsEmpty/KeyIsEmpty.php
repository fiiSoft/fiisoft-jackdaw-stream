<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\CheckType\IsEmpty;

use FiiSoft\Jackdaw\Filter\CheckType\IsEmpty;

final class KeyIsEmpty extends IsEmpty
{
    /**
     * @inheritDoc
     */
    public function isAllowed($value, $key = null): bool
    {
        return empty($key);
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if (empty($key)) {
                yield $key => $value;
            }
        }
    }
}