<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Simple\NotSame;

use FiiSoft\Jackdaw\Filter\Simple\NotSame;

final class ValueNotSame extends NotSame
{
    /**
     * @inheritDoc
     */
    public function isAllowed($value, $key = null): bool
    {
        return $value !== $this->desired;
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if ($value !== $this->desired) {
                yield $key => $value;
            }
        }
    }
}