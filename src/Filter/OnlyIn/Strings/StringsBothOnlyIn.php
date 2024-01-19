<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\OnlyIn\Strings;

use FiiSoft\Jackdaw\Filter\OnlyIn\OnlyIn;

final class StringsBothOnlyIn extends OnlyIn
{
    /**
     * @inheritDoc
     */
    public function isAllowed($value, $key = null): bool
    {
        return \is_string($key) && \is_string($value) && isset($this->strings[$value], $this->strings[$key]);
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if (\is_string($key) && \is_string($value) && isset($this->strings[$value], $this->strings[$key])) {
                yield $key => $value;
            }
        }
    }
}