<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\OnlyIn\Mixed;

use FiiSoft\Jackdaw\Filter\OnlyIn\OnlyIn;

final class MixedKeyOnlyIn extends OnlyIn
{
    /**
     * @inheritDoc
     */
    public function isAllowed($value, $key = null): bool
    {
        if (\is_int($key)) {
            return isset($this->ints[$key]);
        }
        
        if (\is_string($key)) {
            return isset($this->strings[$key]);
        }
        
        return \in_array($key, $this->other);
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if (\is_int($key)) {
                if (isset($this->ints[$key])) {
                    yield $key => $value;
                }
            } elseif (\is_string($key)) {
                if (isset($this->strings[$key])) {
                    yield $key => $value;
                }
            } elseif (\in_array($key, $this->other)) {
                yield $key => $value;
            }
        }
    }
}