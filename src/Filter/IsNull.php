<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter;

use FiiSoft\Jackdaw\Internal\Check;

final class IsNull implements Filter
{
    public function isAllowed($value, $key, int $mode = Check::VALUE): bool
    {
        switch ($mode) {
            case Check::VALUE:
                return $value === null;
            case Check::KEY:
                return $key === null;
            case Check::BOTH:
                return $value === null && $key === null;
            case Check::ANY:
                return $value === null || $key === null;
            default:
                throw new \InvalidArgumentException('Invalid param mode');
        }
    }
}