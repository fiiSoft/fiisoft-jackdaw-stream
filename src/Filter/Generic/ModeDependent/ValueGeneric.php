<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Generic\ModeDependent;

use FiiSoft\Jackdaw\Filter\Generic\ModeDependent;

final class ValueGeneric extends ModeDependent
{
    /**
     * @inheritDoc
     */
    public function isAllowed($value, $key = null): bool
    {
        return $this->expected === ($this->callable)($value);
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if ($this->expected === ($this->callable)($value)) {
                yield $key => $value;
            }
        }
    }
}