<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Number;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Internal\Check;

final class IsOdd implements Filter
{
    /**
     * @inheritdoc
     */
    public function isAllowed($value, $key, int $mode = Check::VALUE): bool
    {
        switch ($mode) {
            case Check::VALUE:
                return ($value & 1) === 1;
            case Check::KEY:
                return ($key & 1) === 1;
            case Check::BOTH:
                return ($value & 1) === 1 && ($key & 1) === 1;
            case Check::ANY:
                return ($value & 1) === 1 || ($key & 1) === 1;
            default:
                throw new \InvalidArgumentException('Invalid param mode');
        }
    }
}