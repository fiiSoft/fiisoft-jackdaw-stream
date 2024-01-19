<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Size\Length;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\Size\SizeFilter;
use FiiSoft\Jackdaw\Internal\Check;

abstract class LengthFilter extends SizeFilter
{
    final public static function create(int $mode, Filter $filter): self
    {
        switch ($mode) {
            case Check::VALUE:
                return new ValueLength($mode, $filter);
            case Check::KEY:
                return new KeyLength($mode, $filter);
            case Check::BOTH:
                return new BothLength($mode, $filter);
            case Check::ANY:
                return new AnyLength($mode, $filter);
            default:
                throw Check::invalidModeException($mode);
        }
    }
}