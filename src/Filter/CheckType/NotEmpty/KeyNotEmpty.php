<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\CheckType\NotEmpty;

use FiiSoft\Jackdaw\Filter\CheckType\NotEmpty;

final class KeyNotEmpty extends NotEmpty
{
    /**
     * @inheritDoc
     */
    public function isAllowed($value, $key = null): bool
    {
        return !empty($key);
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if (!empty($key)) {
                yield $key => $value;
            }
        }
    }
}