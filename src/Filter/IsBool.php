<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter;

use FiiSoft\Jackdaw\Internal\Check;

final class IsBool implements Filter
{
    public function isAllowed($value, $key, int $mode = Check::VALUE): bool
    {
        switch ($mode) {
            case Check::VALUE:
                return \is_bool($value);
            case Check::KEY:
                return \is_bool($key);
            case Check::BOTH:
                return \is_bool($value) && \is_bool($key);
            case Check::ANY:
                return \is_bool($value) || \is_bool($key);
            default:
                throw new \InvalidArgumentException('Invalid param mode');
        }
    }
}