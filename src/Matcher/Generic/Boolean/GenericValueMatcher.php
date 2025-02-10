<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Matcher\Generic\Boolean;

use FiiSoft\Jackdaw\Matcher\Generic\BaseGenericMatcher;

final class GenericValueMatcher extends BaseGenericMatcher
{
    /**
     * @inheritDoc
     */
    public function matches($value1, $value2, $key1 = null, $key2 = null): bool
    {
        return ($this->callable)($value1, $value2);
    }
}