<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Number\LessOrEqual;

use FiiSoft\Jackdaw\Filter\Number\LessOrEqual;

final class AnyLessOrEqual extends LessOrEqual
{
    /**
     * @inheritDoc
     */
    public function isAllowed($value, $key = null): bool
    {
        return $value <= $this->number || $key <= $this->number;
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if ($value <= $this->number || $key <= $this->number) {
                yield $key => $value;
            }
        }
    }
}