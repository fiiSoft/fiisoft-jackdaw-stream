<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Number\NotInside;

use FiiSoft\Jackdaw\Filter\Number\NotInside;

final class ValueNotInside extends NotInside
{
    /**
     * @inheritDoc
     */
    public function isAllowed($value, $key = null): bool
    {
        return $value <= $this->lower || $value >= $this->higher;
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if ($value <= $this->lower || $value >= $this->higher) {
                yield $key => $value;
            }
        }
    }
}