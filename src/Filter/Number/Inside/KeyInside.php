<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Number\Inside;

use FiiSoft\Jackdaw\Filter\Number\Inside;

final class KeyInside extends Inside
{
    /**
     * @inheritDoc
     */
    public function isAllowed($value, $key = null): bool
    {
        return $key > $this->lower && $key < $this->higher;
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if ($key > $this->lower && $key < $this->higher) {
                yield $key => $value;
            }
        }
    }
}