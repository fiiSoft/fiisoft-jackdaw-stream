<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Matcher\Generic\Integer;

use FiiSoft\Jackdaw\Matcher\Generic\BaseGenericMatcher;

final class ComparativeBothMatcher extends BaseGenericMatcher
{
    /**
     * @inheritDoc
     */
    public function matches($value1, $value2, $key1 = null, $key2 = null): bool
    {
        return ($this->callable)($value1, $value2) === 0 && ($this->callable)($key1, $key2) === 0;
    }
}