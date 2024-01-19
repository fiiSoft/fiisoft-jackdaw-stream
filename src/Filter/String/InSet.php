<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\String;

use FiiSoft\Jackdaw\Filter\String\InSet\AnyInSet;
use FiiSoft\Jackdaw\Filter\String\InSet\BothInSet;
use FiiSoft\Jackdaw\Filter\String\InSet\KeyInSet;
use FiiSoft\Jackdaw\Filter\String\InSet\ValueInSet;
use FiiSoft\Jackdaw\Internal\Check;

abstract class InSet extends StringFilterMulti
{
    final public static function create(int $mode, array $values, bool $ignoreCase = false): self
    {
        switch ($mode) {
            case Check::VALUE:
                return new ValueInSet($mode, $values, $ignoreCase);
            case Check::KEY:
                return new KeyInSet($mode, $values, $ignoreCase);
            case Check::BOTH:
                return new BothInSet($mode, $values, $ignoreCase);
            case Check::ANY:
                return new AnyInSet($mode, $values, $ignoreCase);
            default:
                throw Check::invalidModeException($mode);
        }
    }
    
    final public function negate(): StringFilter
    {
        return NotInSet::create($this->negatedMode(), $this->oryginal, $this->ignoreCase);
    }
}