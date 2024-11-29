<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\String;

use FiiSoft\Jackdaw\Filter\String\NotInSet\AnyNotInSet;
use FiiSoft\Jackdaw\Filter\String\NotInSet\BothNotInSet;
use FiiSoft\Jackdaw\Filter\String\NotInSet\KeyNotInSet;
use FiiSoft\Jackdaw\Filter\String\NotInSet\ValueNotInSet;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Internal\Mode;

abstract class NotInSet extends StringFilterMulti
{
    /**
     * @param string[] $values
     */
    final public static function create(int $mode, array $values, bool $ignoreCase = false): self
    {
        switch ($mode) {
            case Check::VALUE:
                return new ValueNotInSet($mode, $values, $ignoreCase);
            case Check::KEY:
                return new KeyNotInSet($mode, $values, $ignoreCase);
            case Check::BOTH:
                return new BothNotInSet($mode, $values, $ignoreCase);
            case Check::ANY:
                return new AnyNotInSet($mode, $values, $ignoreCase);
            default:
                throw Mode::invalidModeException($mode);
        }
    }
    
    final public function negate(): StringFilter
    {
        return InSet::create($this->negatedMode(), $this->oryginal, $this->ignoreCase);
    }
}