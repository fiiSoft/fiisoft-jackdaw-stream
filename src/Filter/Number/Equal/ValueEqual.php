<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Number\Equal;

use FiiSoft\Jackdaw\Filter\Number\Equal;

final class ValueEqual extends Equal
{
    /**
     * @inheritDoc
     */
    public function isAllowed($value, $key = null): bool
    {
        return $value == $this->number;
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if ($value == $this->number) {
                yield $key => $value;
            }
        }
    }
}