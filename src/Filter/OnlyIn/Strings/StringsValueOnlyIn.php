<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\OnlyIn\Strings;

use FiiSoft\Jackdaw\Filter\OnlyIn\OnlyIn;

final class StringsValueOnlyIn extends OnlyIn
{
    /**
     * @inheritDoc
     */
    public function isAllowed($value, $key = null): bool
    {
        return \is_string($value) && isset($this->strings[$value]);
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if (\is_string($value) && isset($this->strings[$value])) {
                yield $key => $value;
            }
        }
    }
}