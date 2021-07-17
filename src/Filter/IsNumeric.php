<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter;

use FiiSoft\Jackdaw\Internal\Check;

final class IsNumeric implements Filter
{
    public function isAllowed($value, $key, int $mode = Check::VALUE): bool
    {
        switch ($mode) {
            case Check::VALUE:
                return \is_numeric($value);
            case Check::KEY:
                return \is_numeric($key);
            case Check::BOTH:
                return \is_numeric($value) && \is_numeric($key);
            case Check::ANY:
                return \is_numeric($value) || \is_numeric($key);
            default:
                throw new \InvalidArgumentException('Invalid param mode');
        }
    }
}