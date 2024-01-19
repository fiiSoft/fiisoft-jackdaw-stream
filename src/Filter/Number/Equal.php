<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Number;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\Number\Equal\AnyEqual;
use FiiSoft\Jackdaw\Filter\Number\Equal\BothEqual;
use FiiSoft\Jackdaw\Filter\Number\Equal\KeyEqual;
use FiiSoft\Jackdaw\Filter\Number\Equal\ValueEqual;
use FiiSoft\Jackdaw\Internal\Check;

abstract class Equal extends SingleArg
{
    /**
     * @param float|int $value
     */
    final public static function create(int $mode, $value): self
    {
        switch ($mode) {
            case Check::VALUE:
                return new ValueEqual($mode, $value);
            case Check::KEY:
                return new KeyEqual($mode, $value);
            case Check::BOTH:
                return new BothEqual($mode, $value);
            case Check::ANY:
                return new AnyEqual($mode, $value);
            default:
                throw Check::invalidModeException($mode);
        }
    }
    
    final public function negate(): Filter
    {
        return NotEqual::create($this->negatedMode(), $this->number);
    }
}