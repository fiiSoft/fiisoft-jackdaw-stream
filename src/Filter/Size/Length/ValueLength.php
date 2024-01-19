<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Size\Length;

final class ValueLength extends LengthFilter
{
    /**
     * @inheritDoc
     */
    public function isAllowed($value, $key = null): bool
    {
        return $this->filter->isAllowed(\mb_strlen($value));
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if ($this->filter->isAllowed(\mb_strlen($value))) {
                yield $key => $value;
            }
        }
    }
}