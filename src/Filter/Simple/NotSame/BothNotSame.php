<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Simple\NotSame;

use FiiSoft\Jackdaw\Filter\Simple\NotSame;

final class BothNotSame extends NotSame
{
    /**
     * @inheritDoc
     */
    public function isAllowed($value, $key = null): bool
    {
        return $value !== $this->desired && $key !== $this->desired;
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if ($value !== $this->desired && $key !== $this->desired) {
                yield $key => $value;
            }
        }
    }
}