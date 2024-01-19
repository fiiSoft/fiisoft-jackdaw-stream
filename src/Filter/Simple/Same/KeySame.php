<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Simple\Same;

use FiiSoft\Jackdaw\Filter\Simple\Same;

final class KeySame extends Same
{
    /**
     * @inheritDoc
     */
    public function isAllowed($value, $key = null): bool
    {
        return $key === $this->desired;
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if ($key === $this->desired) {
                yield $key => $value;
            }
        }
    }
}