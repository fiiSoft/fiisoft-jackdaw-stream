<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter;

use FiiSoft\Jackdaw\Internal\Check;

final class NotEmpty implements Filter
{
    public function isAllowed($value, $key, int $mode = Check::VALUE): bool
    {
        switch ($mode) {
            case Check::VALUE:
                return !empty($value);
            case Check::KEY:
                return !empty($key);
            case Check::BOTH:
                return !empty($value) && !empty($key);
            case Check::ANY:
                return !empty($value) || !empty($key);
            default:
                throw new \InvalidArgumentException('Invalid param mode');
        }
    }
}