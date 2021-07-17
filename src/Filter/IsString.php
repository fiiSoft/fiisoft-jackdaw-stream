<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter;

use FiiSoft\Jackdaw\Internal\Check;

final class IsString implements Filter
{
    public function isAllowed($value, $key, int $mode = Check::VALUE): bool
    {
        switch ($mode) {
            case Check::VALUE:
                return \is_string($value);
            case Check::KEY:
                return \is_string($key);
            case Check::BOTH:
                return \is_string($value) && \is_string($key);
            case Check::ANY:
                return \is_string($value) || \is_string($key);
            default:
                throw new \InvalidArgumentException('Invalid param mode');
        }
    }
}