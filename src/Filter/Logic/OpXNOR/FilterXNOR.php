<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Logic\OpXNOR;

final class FilterXNOR extends BaseXNOR
{
    /**
     * @inheritDoc
     */
    public function isAllowed($value, $key = null): bool
    {
        return $this->first->isAllowed($value, $key) === $this->second->isAllowed($value, $key);
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if ($this->first->isAllowed($value, $key) === $this->second->isAllowed($value, $key)) {
                yield $key => $value;
            }
        }
    }
    
    public function getMode(): ?int
    {
        return $this->first->getMode() === $this->second->getMode() ? $this->first->getMode() : null;
    }
}