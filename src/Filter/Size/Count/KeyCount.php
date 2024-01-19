<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Size\Count;

final class KeyCount extends CountFilter
{
    /**
     * @inheritDoc
     */
    public function isAllowed($value, $key = null): bool
    {
        return $this->filter->isAllowed(\count($key));
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if ($this->filter->isAllowed(\count($key))) {
                yield $key => $value;
            }
        }
    }
}