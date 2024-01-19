<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\OnlyIn\Mixed;

use FiiSoft\Jackdaw\Filter\OnlyIn\OnlyIn;

final class MixedValueOnlyIn extends OnlyIn
{
    /**
     * @inheritDoc
     */
    public function isAllowed($value, $key = null): bool
    {
        if (\is_int($value)) {
            return isset($this->ints[$value]);
        }

        if (\is_string($value)) {
            return isset($this->strings[$value]);
        }

        return \in_array($value, $this->other);
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if (\is_int($value)) {
                if (isset($this->ints[$value])) {
                    yield $key => $value;
                }
            } elseif (\is_string($value)) {
                if (isset($this->strings[$value])) {
                    yield $key => $value;
                }
            } elseif (\in_array($value, $this->other)) {
                yield $key => $value;
            }
        }
    }
}