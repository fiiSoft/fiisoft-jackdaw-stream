<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\String;

use FiiSoft\Jackdaw\Filter\String\NotContains\AnyNotContains;
use FiiSoft\Jackdaw\Filter\String\NotContains\BothNotContains;
use FiiSoft\Jackdaw\Filter\String\NotContains\KeyNotContains;
use FiiSoft\Jackdaw\Filter\String\NotContains\ValueNotContains;
use FiiSoft\Jackdaw\Internal\Check;

abstract class NotContains extends StringFilterSingle
{
    final public static function create(int $mode, string $value, bool $ignoreCase = false): self
    {
        switch ($mode) {
            case Check::VALUE:
                return new ValueNotContains($mode, $value, $ignoreCase);
            case Check::KEY:
                return new KeyNotContains($mode, $value, $ignoreCase);
            case Check::BOTH:
                return new BothNotContains($mode, $value, $ignoreCase);
            case Check::ANY:
                return new AnyNotContains($mode, $value, $ignoreCase);
            default:
                throw Check::invalidModeException($mode);
        }
    }
    
    final public function negate(): StringFilter
    {
        return Contains::create($this->negatedMode(), $this->value, $this->ignoreCase);
    }
}