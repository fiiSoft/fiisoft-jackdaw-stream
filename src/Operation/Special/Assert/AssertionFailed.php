<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Special\Assert;

use FiiSoft\Jackdaw\Internal\Helper;

final class AssertionFailed extends \RuntimeException
{
    /**
     * @param mixed $value
     * @param mixed $key
     */
    public static function exception($value, $key, int $mode): self
    {
        return new self(
            'Element does not satisfy expectations. Mode: '.$mode
            .', value: '.Helper::describe($value).', key: '.Helper::describe($key)
        );
    }
}