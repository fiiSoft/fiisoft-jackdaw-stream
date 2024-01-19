<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Size\Count;

final class ValueCount extends CountFilter
{
    /**
     * @inheritDoc
     */
    public function isAllowed($value, $key = null): bool
    {
        return $this->filter->isAllowed(\count($value));
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if ($this->filter->isAllowed(\count($value))) {
                yield $key => $value;
            }
        }
    }
}