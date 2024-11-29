<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\String;

use FiiSoft\Jackdaw\Filter\String\NotStartsWith\AnyNotStartsWith;
use FiiSoft\Jackdaw\Filter\String\NotStartsWith\BothNotStartsWith;
use FiiSoft\Jackdaw\Filter\String\NotStartsWith\KeyNotStartsWith;
use FiiSoft\Jackdaw\Filter\String\NotStartsWith\ValueNotStartsWith;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Internal\Mode;

abstract class NotStartsWith extends StringFilterSingle
{
    final public static function create(int $mode, string $value, bool $ignoreCase = false): self
    {
        switch ($mode) {
            case Check::VALUE:
                return new ValueNotStartsWith($mode, $value, $ignoreCase);
            case Check::KEY:
                return new KeyNotStartsWith($mode, $value, $ignoreCase);
            case Check::BOTH:
                return new BothNotStartsWith($mode, $value, $ignoreCase);
            case Check::ANY:
                return new AnyNotStartsWith($mode, $value, $ignoreCase);
            default:
                throw Mode::invalidModeException($mode);
        }
    }
    
    final public function negate(): StringFilter
    {
        return StartsWith::create($this->negatedMode(), $this->value, $this->ignoreCase);
    }
}