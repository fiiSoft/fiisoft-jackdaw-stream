<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Number;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Internal\Check;

final class IsEven implements Filter
{
    /**
     * @inheritdoc
     */
    public function isAllowed($value, $key, int $mode = Check::VALUE): bool
    {
        switch ($mode) {
            case Check::VALUE:
                return ($value & 1) === 0;
            case Check::KEY:
                return ($key & 1) === 0;
            case Check::BOTH:
                return ($value & 1) === 0 && ($key & 1) === 0;
            case Check::ANY:
                return ($value & 1) === 0 || ($key & 1) === 0;
            default:
                throw new \InvalidArgumentException('Invalid param mode');
        }
    }
}