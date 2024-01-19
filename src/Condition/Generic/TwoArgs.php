<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Condition\Generic;

use FiiSoft\Jackdaw\Condition\GenericCondition;

final class TwoArgs extends GenericCondition
{
    /**
     * @inheritDoc
     */
    public function isTrueFor($value, $key): bool
    {
        return ($this->callable)($value, $key);
    }
}