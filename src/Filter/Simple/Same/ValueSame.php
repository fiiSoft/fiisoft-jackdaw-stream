<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Simple\Same;

use FiiSoft\Jackdaw\Filter\Simple\Same;

final class ValueSame extends Same
{
    /**
     * @inheritDoc
     */
    public function isAllowed($value, $key = null): bool
    {
        return $value === $this->desired;
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if ($value === $this->desired) {
                yield $key => $value;
            }
        }
    }
}