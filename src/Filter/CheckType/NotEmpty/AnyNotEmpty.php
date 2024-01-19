<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\CheckType\NotEmpty;

use FiiSoft\Jackdaw\Filter\CheckType\NotEmpty;

final class AnyNotEmpty extends NotEmpty
{
    /**
     * @inheritDoc
     */
    public function isAllowed($value, $key = null): bool
    {
        return !empty($key) || !empty($value);
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if (!empty($key) || !empty($value)) {
                yield $key => $value;
            }
        }
    }
}