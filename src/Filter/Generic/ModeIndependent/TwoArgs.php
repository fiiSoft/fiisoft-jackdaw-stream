<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Generic\ModeIndependent;

use FiiSoft\Jackdaw\Filter\Generic\ModeIndependent;

final class TwoArgs extends ModeIndependent
{
    /**
     * @inheritDoc
     */
    public function isAllowed($value, $key = null): bool
    {
        return $this->expected === ($this->callable)($value, $key);
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if ($this->expected === ($this->callable)($value, $key)) {
                yield $key => $value;
            }
        }
    }
}