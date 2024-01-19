<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\String;

use FiiSoft\Jackdaw\Filter\String\EndsWith\AnyEndsWith;
use FiiSoft\Jackdaw\Filter\String\EndsWith\BothEndsWith;
use FiiSoft\Jackdaw\Filter\String\EndsWith\KeyEndsWith;
use FiiSoft\Jackdaw\Filter\String\EndsWith\ValueEndsWith;
use FiiSoft\Jackdaw\Internal\Check;

abstract class EndsWith extends StringFilterSingle
{
    final public static function create(int $mode, string $value, bool $ignoreCase = false): self
    {
        switch ($mode) {
            case Check::VALUE:
                return new ValueEndsWith($mode, $value, $ignoreCase);
            case Check::KEY:
                return new KeyEndsWith($mode, $value, $ignoreCase);
            case Check::BOTH:
                return new BothEndsWith($mode, $value, $ignoreCase);
            case Check::ANY:
                return new AnyEndsWith($mode, $value, $ignoreCase);
            default:
                throw Check::invalidModeException($mode);
        }
    }
    
    final public function negate(): StringFilter
    {
        return NotEndsWith::create($this->negatedMode(), $this->value, $this->ignoreCase);
    }
}