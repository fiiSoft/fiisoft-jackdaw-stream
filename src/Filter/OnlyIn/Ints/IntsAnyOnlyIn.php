<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\OnlyIn\Ints;

use FiiSoft\Jackdaw\Filter\OnlyIn\OnlyIn;

final class IntsAnyOnlyIn extends OnlyIn
{
    /**
     * @inheritDoc
     */
    public function isAllowed($value, $key = null): bool
    {
        return \is_int($value) && isset($this->ints[$value]) || \is_int($key) && isset($this->ints[$key]);
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if (\is_int($value) && isset($this->ints[$value]) || \is_int($key) && isset($this->ints[$key])) {
                yield $key => $value;
            }
        }
    }
}