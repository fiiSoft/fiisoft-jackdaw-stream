<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\String;

use FiiSoft\Jackdaw\Filter\String\NotEndsWith\AnyNotEndsWith;
use FiiSoft\Jackdaw\Filter\String\NotEndsWith\BothNotEndsWith;
use FiiSoft\Jackdaw\Filter\String\NotEndsWith\KeyNotEndsWith;
use FiiSoft\Jackdaw\Filter\String\NotEndsWith\ValueNotEndsWith;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Internal\Mode;

abstract class NotEndsWith extends StringFilterSingle
{
    final public static function create(int $mode, string $value, bool $ignoreCase = false): self
    {
        switch ($mode) {
            case Check::VALUE:
                return new ValueNotEndsWith($mode, $value, $ignoreCase);
            case Check::KEY:
                return new KeyNotEndsWith($mode, $value, $ignoreCase);
            case Check::BOTH:
                return new BothNotEndsWith($mode, $value, $ignoreCase);
            case Check::ANY:
                return new AnyNotEndsWith($mode, $value, $ignoreCase);
            default:
                throw Mode::invalidModeException($mode);
        }
    }
    
    final public function negate(): StringFilter
    {
        return EndsWith::create($this->negatedMode(), $this->value, $this->ignoreCase);
    }
}