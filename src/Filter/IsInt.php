<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter;

use FiiSoft\Jackdaw\Internal\Check;

final class IsInt implements Filter
{
    public function isAllowed($value, $key, int $mode = Check::VALUE): bool
    {
        switch ($mode) {
            case Check::VALUE:
                return \is_int($value);
            case Check::KEY:
                return \is_int($key);
            case Check::BOTH:
                return \is_int($value) && \is_int($key);
            case Check::ANY:
                return \is_int($value) || \is_int($key);
            default:
                throw new \InvalidArgumentException('Invalid param mode');
        }
    }
}