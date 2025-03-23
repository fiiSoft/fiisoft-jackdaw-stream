<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Size\Length;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\Size\SizeFilter;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Internal\Mode;

abstract class LengthFilter extends SizeFilter
{
    final public static function create(int $mode, Filter $filter): self
    {
        switch ($mode) {
            case Check::VALUE:
                return new ValueLength($filter, $mode);
            case Check::KEY:
                return new KeyLength($filter, $mode);
            case Check::BOTH:
                return new BothLength($filter, $mode);
            case Check::ANY:
                return new AnyLength($filter, $mode);
            default:
                throw Mode::invalidModeException($mode);
        }
    }
}