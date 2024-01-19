<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Number\LessThan;

use FiiSoft\Jackdaw\Filter\Number\LessThan;

final class ValueLessThan extends LessThan
{
    /**
     * @inheritDoc
     */
    public function isAllowed($value, $key = null): bool
    {
        return $value < $this->number;
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if ($value < $this->number) {
                yield $key => $value;
            }
        }
    }
}