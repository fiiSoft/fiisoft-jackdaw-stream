<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Number\NotEqual;

use FiiSoft\Jackdaw\Filter\Number\NotEqual;

final class KeyNotEqual extends NotEqual
{
    /**
     * @inheritDoc
     */
    public function isAllowed($value, $key = null): bool
    {
        return $key != $this->number;
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if ($key != $this->number) {
                yield $key => $value;
            }
        }
    }
}