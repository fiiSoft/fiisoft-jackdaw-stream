<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Number\Outside;

use FiiSoft\Jackdaw\Filter\Number\Outside;

final class KeyOutside extends Outside
{
    /**
     * @inheritDoc
     */
    public function isAllowed($value, $key = null): bool
    {
        return $key < $this->lower || $key > $this->higher;
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if ($key < $this->lower || $key > $this->higher) {
                yield $key => $value;
            }
        }
    }
}