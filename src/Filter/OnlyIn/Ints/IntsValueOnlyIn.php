<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\OnlyIn\Ints;

use FiiSoft\Jackdaw\Filter\OnlyIn\OnlyIn;

final class IntsValueOnlyIn extends OnlyIn
{
    /**
     * @inheritDoc
     */
    public function isAllowed($value, $key = null): bool
    {
        return \is_int($value) && isset($this->ints[$value]);
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if (\is_int($value) && isset($this->ints[$value])) {
                yield $key => $value;
            }
        }
    }
}