<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Number\Inside;

use FiiSoft\Jackdaw\Filter\Number\Inside;

final class ValueInside extends Inside
{
    /**
     * @inheritDoc
     */
    public function isAllowed($value, $key = null): bool
    {
        return $value > $this->lower && $value < $this->higher;
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if ($value > $this->lower && $value < $this->higher) {
                yield $key => $value;
            }
        }
    }
}