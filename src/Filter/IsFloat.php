<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter;

use FiiSoft\Jackdaw\Internal\Check;

final class IsFloat implements Filter
{
    public function isAllowed($value, $key, int $mode = Check::VALUE): bool
    {
        switch ($mode) {
            case Check::VALUE:
                return \is_float($value);
            case Check::KEY:
                return \is_float($key);
            case Check::BOTH:
                return \is_float($value) && \is_float($key);
            case Check::ANY:
                return \is_float($value) || \is_float($key);
            default:
                throw new \InvalidArgumentException('Invalid param mode');
        }
    }
}