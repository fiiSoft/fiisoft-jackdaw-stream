<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Generic\ModeDependent;

use FiiSoft\Jackdaw\Filter\Generic\ModeDependent;

final class AnyGeneric extends ModeDependent
{
    /**
     * @inheritDoc
     */
    public function isAllowed($value, $key = null): bool
    {
        return $this->expected === ($this->callable)($value)
            || $this->expected === ($this->callable)($key);
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if ($this->expected === ($this->callable)($value)
                || $this->expected === ($this->callable)($key)
            ) {
                yield $key => $value;
            }
        }
    }
}