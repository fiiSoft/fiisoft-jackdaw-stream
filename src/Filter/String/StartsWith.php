<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\String;

use FiiSoft\Jackdaw\Filter\String\StartsWith\AnyStartsWith;
use FiiSoft\Jackdaw\Filter\String\StartsWith\BothStartsWith;
use FiiSoft\Jackdaw\Filter\String\StartsWith\KeyStartsWith;
use FiiSoft\Jackdaw\Filter\String\StartsWith\ValueStartsWith;
use FiiSoft\Jackdaw\Internal\Check;

abstract class StartsWith extends StringFilterSingle
{
    final public static function create(int $mode, string $value, bool $ignoreCase = false): self
    {
        switch ($mode) {
            case Check::VALUE:
                return new ValueStartsWith($mode, $value, $ignoreCase);
            case Check::KEY:
                return new KeyStartsWith($mode, $value, $ignoreCase);
            case Check::BOTH:
                return new BothStartsWith($mode, $value, $ignoreCase);
            case Check::ANY:
                return new AnyStartsWith($mode, $value, $ignoreCase);
            default:
                throw Check::invalidModeException($mode);
        }
    }
    
    final public function negate(): StringFilter
    {
        return NotStartsWith::create($this->negatedMode(), $this->value, $this->ignoreCase);
    }
}