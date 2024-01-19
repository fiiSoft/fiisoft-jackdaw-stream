<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Number\GreaterThan;

use FiiSoft\Jackdaw\Filter\Number\GreaterThan;

final class KeyGreaterThan extends GreaterThan
{
    /**
     * @inheritDoc
     */
    public function isAllowed($value, $key = null): bool
    {
        return $key > $this->number;
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if ($key > $this->number) {
                yield $key => $value;
            }
        }
    }
}