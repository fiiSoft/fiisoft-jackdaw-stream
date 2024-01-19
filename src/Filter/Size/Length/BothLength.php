<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Size\Length;

final class BothLength extends LengthFilter
{
    /**
     * @inheritDoc
     */
    public function isAllowed($value, $key = null): bool
    {
        return $this->filter->isAllowed(\mb_strlen($value)) && $this->filter->isAllowed(\mb_strlen($key));
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if ($this->filter->isAllowed(\mb_strlen($value)) && $this->filter->isAllowed(\mb_strlen($key))) {
                yield $key => $value;
            }
        }
    }
}