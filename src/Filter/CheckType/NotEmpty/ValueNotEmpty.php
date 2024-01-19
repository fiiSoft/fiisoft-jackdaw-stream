<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\CheckType\NotEmpty;

use FiiSoft\Jackdaw\Filter\CheckType\NotEmpty;

final class ValueNotEmpty extends NotEmpty
{
    /**
     * @inheritDoc
     */
    public function isAllowed($value, $key = null): bool
    {
        return !empty($value);
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if (!empty($value)) {
                yield $key => $value;
            }
        }
    }
}