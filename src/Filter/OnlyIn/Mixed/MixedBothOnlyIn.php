<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\OnlyIn\Mixed;

use FiiSoft\Jackdaw\Filter\OnlyIn\OnlyIn;

final class MixedBothOnlyIn extends OnlyIn
{
    /**
     * @inheritDoc
     */
    public function isAllowed($value, $key = null): bool
    {
        if (\is_int($value)) {
            if (isset($this->ints[$value])) {
                goto CHECK_KEY;
            }
        } elseif (\is_string($value)) {
            if (isset($this->strings[$value])) {
                goto CHECK_KEY;
            }
        } elseif (\in_array($value, $this->other)) {
            goto CHECK_KEY;
        }
        
        return false;
        
        CHECK_KEY:
        
        if (\is_int($key)) {
            if (isset($this->ints[$key])) {
                return true;
            }
        } elseif (\is_string($key)) {
            if (isset($this->strings[$key])) {
                return true;
            }
        } else {
            return \in_array($key, $this->other);
        }
        
        return false;
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if (\is_int($value)) {
                if (isset($this->ints[$value])) {
                    goto CHECK_KEY;
                }
            } elseif (\is_string($value)) {
                if (isset($this->strings[$value])) {
                    goto CHECK_KEY;
                }
            } elseif (\in_array($value, $this->other)) {
                goto CHECK_KEY;
            }
            
            continue;
            
            CHECK_KEY:
            
            if (\is_int($key)) {
                if (isset($this->ints[$key])) {
                    yield $key => $value;
                }
            } elseif (\is_string($key)) {
                if (isset($this->strings[$key])) {
                    yield $key => $value;
                }
            } elseif(\in_array($key, $this->other)) {
                yield $key => $value;
            }
        }
    }
}