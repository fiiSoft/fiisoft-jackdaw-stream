<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\OnlyIn\Strings;

use FiiSoft\Jackdaw\Filter\OnlyIn\OnlyIn;

final class StringsKeyOnlyIn extends OnlyIn
{
    /**
     * @inheritDoc
     */
    public function isAllowed($value, $key = null): bool
    {
        return \is_string($key) && isset($this->strings[$key]);
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if (\is_string($key) && isset($this->strings[$key])) {
                yield $key => $value;
            }
        }
    }
}