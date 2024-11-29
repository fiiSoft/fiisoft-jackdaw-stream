<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Size\Count;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\Size\SizeFilter;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Internal\Mode;

abstract class CountFilter extends SizeFilter
{
    final public static function create(int $mode, Filter $filter): self
    {
        switch ($mode) {
            case Check::VALUE:
                return new ValueCount($mode, $filter);
            case Check::KEY:
                return new KeyCount($mode, $filter);
            case Check::BOTH:
                return new BothCount($mode, $filter);
            case Check::ANY:
                return new AnyCount($mode, $filter);
            default:
                throw Mode::invalidModeException($mode);
        }
    }
}