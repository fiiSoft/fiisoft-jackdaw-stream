<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Number\Between;

use FiiSoft\Jackdaw\Filter\Number\Between;

final class BothBetween extends Between
{
    /**
     * @inheritDoc
     */
    public function isAllowed($value, $key = null): bool
    {
        return $value >= $this->lower && $value <= $this->higher && $key >= $this->lower && $key <= $this->higher;
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if ($value >= $this->lower && $value <= $this->higher && $key >= $this->lower && $key <= $this->higher) {
                yield $key => $value;
            }
        }
    }
}