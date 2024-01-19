<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Number\Equal;

use FiiSoft\Jackdaw\Filter\Number\Equal;

final class BothEqual extends Equal
{
    /**
     * @inheritDoc
     */
    public function isAllowed($value, $key = null): bool
    {
        return $key == $this->number && $value == $this->number;
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if ($key == $this->number && $value == $this->number) {
                yield $key => $value;
            }
        }
    }
}